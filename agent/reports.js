import pool from './db.js';

/**
 * Reúne las métricas de ventas y operación de un día específico.
 * Todas las fechas se evalúan contra la zona horaria del servidor MySQL (la misma
 * que usa createOrder con NOW()), por lo que "hoy" = CURDATE() es consistente con la escritura.
 * @param {string} [dateStr] - Fecha objetivo YYYY-MM-DD. Por defecto hoy (CURDATE()).
 * @returns {Promise<object>} Métricas estructuradas del día.
 */
export async function getDailySalesSummary(dateStr) {
  const day = dateStr || null; // null => usamos CURDATE() en SQL
  const dParam = day ? '?' : 'CURDATE()';
  const args = day ? [day] : [];

  // 1. Ventas del día (excluye cancelados)
  const [[ventas]] = await pool.execute(
    `SELECT COUNT(*) AS pedidos,
            COALESCE(SUM(total),0)         AS total,
            COALESCE(SUM(subtotal),0)      AS subtotal,
            COALESCE(SUM(descuento),0)     AS descuento,
            COALESCE(SUM(costo_delivery),0) AS delivery
       FROM pedidos
      WHERE DATE(creado_en) = ${dParam} AND estado <> 'cancelado'`, args);

  // 2. Clientes: nuevos vs recurrentes (según si el teléfono tenía pedidos antes de hoy)
  const [clientes] = await pool.execute(
    `SELECT p.telefono,
            (SELECT COUNT(*) FROM pedidos p2
              WHERE p2.telefono = p.telefono
                AND DATE(p2.creado_en) < ${dParam}) AS previos
       FROM pedidos p
      WHERE DATE(p.creado_en) = ${dParam} AND p.estado <> 'cancelado'
      GROUP BY p.telefono`, [...args, ...args]);
  const nuevos = clientes.filter(c => Number(c.previos) === 0).length;
  const recurrentes = clientes.filter(c => Number(c.previos) > 0).length;

  // 3. Cancelaciones del día (por fecha de actualización, que es cuando se anuló)
  const [[cancel]] = await pool.execute(
    `SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS monto
       FROM pedidos
      WHERE estado = 'cancelado' AND DATE(actualizado_en) = ${dParam}`, args);

  // 4. Pedidos incompletos creados hoy (pendientes sin despacho agendado)
  const [[incompletosHoy]] = await pool.execute(
    `SELECT COUNT(*) AS n
       FROM pedidos
      WHERE DATE(creado_en) = ${dParam} AND estado = 'pendiente'
        AND (fecha_despacho IS NULL OR hora_despacho IS NULL OR hora_despacho = '')`, args);

  // 5. Backlog total de incompletos (todos los pendientes sin despacho, no solo hoy)
  const [[incompletosTotal]] = await pool.execute(
    `SELECT COUNT(*) AS n
       FROM pedidos
      WHERE estado = 'pendiente'
        AND (fecha_despacho IS NULL OR hora_despacho IS NULL OR hora_despacho = '')`);

  // 6. Top productos del día
  const [topProductos] = await pool.execute(
    `SELECT pi.nombre, SUM(pi.cantidad) AS unidades
       FROM pedido_items pi
       JOIN pedidos p ON pi.pedido_id = p.id
      WHERE DATE(p.creado_en) = ${dParam} AND p.estado <> 'cancelado'
      GROUP BY pi.nombre
      ORDER BY unidades DESC
      LIMIT 5`, args);

  // 7. Carritos abandonados del día (no recuperados)
  const [[carritos]] = await pool.execute(
    `SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS monto
       FROM carritos_abandonados
      WHERE DATE(creado_en) = ${dParam} AND recuperado = 0`, args);

  return {
    fecha: day || new Date().toLocaleDateString('es-CL', { timeZone: 'America/Santiago' }),
    ventas,
    clientes: { total: clientes.length, nuevos, recurrentes },
    cancel,
    incompletosHoy: incompletosHoy.n,
    incompletosTotal: incompletosTotal.n,
    topProductos,
    carritos
  };
}

const clp = (n) => `$${Number(n || 0).toLocaleString('es-CL')}`;

/**
 * Formatea el resumen diario como un mensaje de WhatsApp legible para el administrador.
 * @param {object} s - Objeto devuelto por getDailySalesSummary
 * @returns {string}
 */
export function formatDailyReport(s) {
  const v = s.ventas;
  const ticket = v.pedidos > 0 ? Math.round(v.total / v.pedidos) : 0;

  const top = s.topProductos.length
    ? s.topProductos.map(p => `   • *${p.unidades}x* ${p.nombre}`).join('\n')
    : '   _Sin ventas registradas hoy_';

  let msg = `📊 *RESUMEN DIARIO MASCOTIENDAS*
🗓️ ${s.fecha}

💰 *VENTAS DEL DÍA*
   Pedidos: *${v.pedidos}*
   Total vendido: *${clp(v.total)}*
   Ticket promedio: ${clp(ticket)}
   (Subtotal ${clp(v.subtotal)} · Despacho ${clp(v.delivery)} · Dctos ${clp(v.descuento)})

👥 *CLIENTES* (${s.clientes.total})
   🆕 Nuevos: *${s.clientes.nuevos}*
   🔁 Recurrentes: *${s.clientes.recurrentes}*

⚠️ *PEDIDOS INCOMPLETOS* (sin despacho agendado)
   Hoy: *${s.incompletosHoy}*
   Pendientes acumulados: *${s.incompletosTotal}*`;

  if (s.cancel.n > 0) {
    msg += `\n\n🚫 *ANULACIONES HOY*: ${s.cancel.n} (${clp(s.cancel.monto)})`;
  }

  if (s.carritos.n > 0) {
    msg += `\n\n🛒 *CARRITOS ABANDONADOS HOY*: ${s.carritos.n} (${clp(s.carritos.monto)} sin concretar)`;
  }

  msg += `\n\n🏆 *TOP PRODUCTOS HOY*\n${top}`;

  return msg;
}

/**
 * Atajo: construye el texto del reporte de un día listo para enviar.
 * @param {string} [dateStr] - Fecha YYYY-MM-DD (por defecto hoy)
 */
export async function buildDailyReport(dateStr) {
  const data = await getDailySalesSummary(dateStr);
  return formatDailyReport(data);
}
