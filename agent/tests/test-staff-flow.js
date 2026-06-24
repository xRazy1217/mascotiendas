// Integración: lenguaje natural de staff -> permiso -> cambio de estado -> summary.
import { parseStaffIntent, canSetEstado } from '../staff.js';
import { updateOrderStatus, getOrderSummary } from '../orders-admin.js';
import pool from '../db.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

async function nuevoPedido(estado = 'pendiente') {
  const [r] = await pool.execute(
    `INSERT INTO pedidos (nombre_cliente, email_cliente, telefono, direccion, ciudad, subtotal, total, estado, metodo_entrega)
     VALUES ('Flujo Test', 't@t.cl', '+56900000000', 'Calle 1', 'La Serena', 1000, 1000, ?, 'delivery')`, [estado]
  );
  return r.insertId;
}

async function main() {
  console.log('\n═══ TEST FLUJO STAFF (integración) ═══\n');
  const ids = [];
  try {
    // — Despacho marca enviado por lenguaje natural —
    const id1 = await nuevoPedido(); ids.push(id1);
    const intent1 = parseStaffIntent(`salí con el ${id1}`);
    check(intent1.action === 'set_status' && intent1.orderId === id1 && intent1.estado === 'enviado', 'despacho: "salí con el N" => enviado');
    check(canSetEstado('despacho', intent1.estado), 'despacho tiene permiso para enviado');
    const r1 = await updateOrderStatus(intent1.orderId, intent1.estado);
    check(r1.success && r1.estadoNuevo === 'enviado', 'pedido pasa a enviado');

    // — Ventas confirma pago por lenguaje natural —
    const id2 = await nuevoPedido(); ids.push(id2);
    const intent2 = parseStaffIntent(`el cliente ya pagó la transferencia del ${id2}`);
    check(intent2.action === 'set_status' && intent2.estado === 'pagado', 'ventas: "pagó la transferencia del N" => pagado');
    check(canSetEstado('ventas', 'pagado'), 'ventas tiene permiso para pagado');
    const r2 = await updateOrderStatus(id2, 'pagado');
    check(r2.success, 'pedido pasa a pagado');
    const sum = await getOrderSummary(id2);
    check(sum && sum.id === id2 && sum.estado === 'pagado', 'getOrderSummary devuelve el pedido para el aviso a despacho');

    // — Permiso denegado: despacho NO puede confirmar pago —
    check(!canSetEstado('despacho', 'pagado'), 'despacho NO puede marcar pagado (bloqueado antes de tocar la BD)');

  } finally {
    for (const id of ids) await pool.execute('DELETE FROM pedidos WHERE id = ?', [id]);
    console.log(`\n(Pedidos de prueba eliminados: ${ids.join(', ')})`);
  }

  console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('ERROR:', e); process.exit(1); });
