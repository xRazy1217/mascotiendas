// Prueba de la gestión de estados de pedido por el administrador.
import { updateOrderStatus, listActionableOrders, formatActionableOrders, ESTADOS_VALIDOS } from '../orders-admin.js';
import pool from '../db.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

async function main() {
  console.log('\n═══ TEST GESTIÓN DE ESTADOS (ADMIN) ═══\n');

  // Crear un pedido de prueba para manipular su estado
  const [ins] = await pool.execute(
    `INSERT INTO pedidos (nombre_cliente, email_cliente, telefono, direccion, ciudad, subtotal, total, estado, metodo_entrega)
     VALUES ('Test Estados', 'test@test.cl', '+56900000000', 'Calle Test 1', 'La Serena', 1000, 1000, 'pendiente', 'delivery')`
  );
  const id = ins.insertId;
  console.log(`Pedido de prueba creado: #${id}\n`);

  try {
    // 1. ID inválido
    check((await updateOrderStatus('abc', 'enviado')).success === false, 'ID inválido se rechaza');

    // 2. Estado inválido
    const r2 = await updateOrderStatus(id, 'volando');
    check(r2.success === false && r2.message.includes('Estado inválido'), 'estado inválido se rechaza');

    // 3. Pedido inexistente
    check((await updateOrderStatus(99999999, 'enviado')).success === false, 'pedido inexistente se rechaza');

    // 4. Cambio válido pendiente -> preparando
    const r4 = await updateOrderStatus(id, 'preparando');
    check(r4.success && r4.estadoAnterior === 'pendiente' && r4.estadoNuevo === 'preparando', 'cambio válido pendiente→preparando');

    // 5. Persistencia real del cambio
    const [[chk]] = await pool.execute('SELECT estado FROM pedidos WHERE id = ?', [id]);
    check(chk.estado === 'preparando', 'el cambio persiste en la BD');

    // 6. Mismo estado se rechaza (no-op)
    check((await updateOrderStatus(id, 'preparando')).success === false, 'mismo estado se rechaza');

    // 7. Listado incluye el pedido (está en preparando = accionable)
    const lista = await listActionableOrders(50);
    check(lista.some(o => o.id === id), 'el listado de accionables incluye el pedido');

    // 8. Al marcar entregado, sale del listado de accionables
    await updateOrderStatus(id, 'entregado');
    const lista2 = await listActionableOrders(50);
    check(!lista2.some(o => o.id === id), 'tras entregar, sale del listado de accionables');

    // 9. Formato no revienta y tiene encabezado
    const txt = formatActionableOrders(lista2);
    check(typeof txt === 'string' && txt.length > 0, 'formato genera texto válido');
    check(formatActionableOrders([]).includes('Todo al día'), 'lista vacía muestra mensaje amable');

    // 10. Enum coherente
    check(ESTADOS_VALIDOS.length === 6 && ESTADOS_VALIDOS.includes('enviado'), 'enum de estados coherente');

  } finally {
    await pool.execute('DELETE FROM pedidos WHERE id = ?', [id]);
    console.log(`\n(Pedido de prueba #${id} eliminado)`);
  }

  console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('ERROR:', e); process.exit(1); });
