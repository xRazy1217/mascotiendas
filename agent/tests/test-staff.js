// Prueba de la lógica de staff: permisos e interpretación de lenguaje natural.
import { canRunCommand, canSetEstado, isAffirmation, isNegation, parseStaffIntent } from '../staff.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

console.log('\n═══ TEST LÓGICA DE STAFF ═══\n');

// ── Permisos ──
check(canSetEstado('despacho', 'enviado') && canSetEstado('despacho', 'entregado'), 'despacho puede enviado/entregado');
check(!canSetEstado('despacho', 'pagado'), 'despacho NO puede pagado');
check(canSetEstado('ventas', 'pagado') && !canSetEstado('ventas', 'enviado'), 'ventas puede pagado, no enviado');
check(canSetEstado('admin', 'entregado'), 'admin puede cualquier estado');
check(canRunCommand('admin', '!setrol') && !canRunCommand('despacho', '!setrol'), '!setrol solo admin');
check(canRunCommand('ventas', '!resumen') && !canRunCommand('despacho', '!resumen'), '!resumen ventas/admin, no despacho');
check(canRunCommand('despacho', '!estado') && canRunCommand('despacho', '!pendientes'), 'despacho puede !estado y !pendientes');

// ── Afirmación / negación ──
check(isAffirmation('sí') && isAffirmation('dale') && isAffirmation('ya po') && isAffirmation('confirmo'), 'afirmaciones reconocidas');
check(isNegation('no') && isNegation('cancela') && isNegation('mejor no'), 'negaciones reconocidas');
check(!isAffirmation('no') && !isNegation('sí'), 'no se confunden sí/no');

// ── Parser de intención ──
const c = (text, exp) => {
  const r = parseStaffIntent(text);
  const ok = Object.keys(exp).every(k => r[k] === exp[k]);
  check(ok, `"${text}" => ${JSON.stringify(r)}`);
};
c('salí con el 67', { action: 'set_status', orderId: 67, estado: 'enviado' });
c('ya entregué el pedido 5', { action: 'set_status', orderId: 5, estado: 'entregado' });
c('estoy preparando el #12', { action: 'set_status', orderId: 12, estado: 'preparando' });
c('el cliente ya pagó la transferencia del 88', { action: 'set_status', orderId: 88, estado: 'pagado' });
c('anula el pedido 43', { action: 'set_status', orderId: 43, estado: 'cancelado' });
c('voy en camino', { action: 'need_id', estado: 'enviado' });
c('qué pedidos tengo pendientes', { action: 'list' });
c('cómo vamos hoy', { action: 'summary' });
c('hola buenas', { action: 'unknown' });
// Prioriza el número junto a "pedido" por sobre una hora suelta
c('entregué el pedido 7 a las 15', { action: 'set_status', orderId: 7, estado: 'entregado' });

console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
process.exit(fail > 0 ? 1 : 0);
