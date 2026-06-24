import pool from './db.js';

// Estados válidos del enum `estado` de la tabla pedidos
export const ESTADOS_VALIDOS = ['pendiente', 'pagado', 'preparando', 'enviado', 'entregado', 'cancelado'];

const clp = (n) => `$${Number(n || 0).toLocaleString('es-CL')}`;
const fmtFecha = (val) => {
  if (!val) return null;
  if (val instanceof Date) return val.toISOString().slice(0, 10);
  return String(val).slice(0, 10);
};

/**
 * Cambia el estado de un pedido validando el ID y el estado destino.
 * @param {number|string} orderId
 * @param {string} nuevoEstado
 * @returns {Promise<object>} { success, message, ... }
 */
export async function updateOrderStatus(orderId, nuevoEstado) {
  const id = Number(orderId);
  if (!Number.isInteger(id) || id <= 0) {
    return { success: false, message: 'ID de pedido inválido.' };
  }
  const estado = String(nuevoEstado || '').trim().toLowerCase();
  if (!ESTADOS_VALIDOS.includes(estado)) {
    return { success: false, message: `Estado inválido. Usa uno de: ${ESTADOS_VALIDOS.join(', ')}.` };
  }

  const [rows] = await pool.execute(
    'SELECT id, nombre_cliente, estado, total FROM pedidos WHERE id = ?', [id]
  );
  if (rows.length === 0) {
    return { success: false, message: `No existe el pedido #${id}.` };
  }
  const order = rows[0];
  if (order.estado === estado) {
    return { success: false, message: `El pedido #${id} ya está en estado '${estado}'.` };
  }

  const estadoAnterior = order.estado;
  await pool.execute('UPDATE pedidos SET estado = ?, actualizado_en = NOW() WHERE id = ?', [estado, id]);

  return {
    success: true,
    message: `✅ Pedido *#${id}* (${order.nombre_cliente}) actualizado: _${estadoAnterior}_ → *${estado}*.`,
    orderId: id,
    estadoAnterior,
    estadoNuevo: estado
  };
}

/**
 * Lista los pedidos que requieren gestión (aún no enviados/entregados/cancelados).
 * @param {number} [limit=10]
 */
export async function listActionableOrders(limit = 10) {
  const safe = Math.max(1, Math.min(50, parseInt(limit) || 10));
  const [rows] = await pool.execute(
    `SELECT id, nombre_cliente, telefono, total, estado, fecha_despacho, hora_despacho
       FROM pedidos
      WHERE estado IN ('pendiente', 'pagado', 'preparando')
      ORDER BY creado_en DESC
      LIMIT ${safe}`
  );
  return rows;
}

/**
 * Formatea la lista de pedidos por gestionar como mensaje de WhatsApp para el admin.
 */
export function formatActionableOrders(rows) {
  if (!rows || rows.length === 0) {
    return '✅ No hay pedidos pendientes de gestión. ¡Todo al día! 🐾';
  }
  const lines = rows.map(o => {
    const f = fmtFecha(o.fecha_despacho);
    const desp = f ? `${f} ${o.hora_despacho || ''}`.trim() : '⚠️ sin agendar';
    return `*#${o.id}* · ${o.nombre_cliente} · ${clp(o.total)}\n   estado: _${o.estado}_ · despacho: ${desp}`;
  });
  return `📋 *PEDIDOS POR GESTIONAR* (${rows.length})\n\n${lines.join('\n')}\n\nPara avanzar uno: *!estado <id> <nuevo>*\nEstados: ${ESTADOS_VALIDOS.join(' / ')}`;
}
