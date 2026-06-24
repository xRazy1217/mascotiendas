// Prueba del módulo de roles: configuración, resolución y ruteo de notificaciones.
import { setRoleNumber, resolveRole, recipientsForEvent, getRoleJid, listRoleConfig, ROLES } from '../roles.js';
import pool from '../db.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

async function main() {
  console.log('\n═══ TEST MÓDULO DE ROLES ═══\n');

  // Guardar valores previos para restaurarlos al final (no ensuciar config real)
  const previos = await listRoleConfig();

  // 1. Validación: rol inválido
  check((await setRoleNumber('chofer', '56911112222')).success === false, 'rol inválido se rechaza');

  // 2. Validación: número inválido
  check((await setRoleNumber('ventas', '123')).success === false, 'número inválido se rechaza');

  // 3. Set válido de ventas y despacho
  await setRoleNumber('ventas', '56911112222');
  await setRoleNumber('despacho', '56933334444');
  check(true, 'set ventas y despacho');

  // 4. resolveRole identifica por últimos 9 dígitos (con prefijo +)
  check((await resolveRole('+56911112222')) === 'ventas', 'resolveRole identifica a ventas');
  check((await resolveRole('933334444')) === 'despacho', 'resolveRole identifica a despacho (9 dígitos)');
  check((await resolveRole('56900000000')) === null, 'teléfono desconocido => null');

  // 5. Ruteo: nuevo pedido va a ventas
  const r1 = await recipientsForEvent('order_created');
  check(r1.includes('56911112222@c.us'), 'order_created enruta a ventas');

  // 6. Ruteo: anulación va a ventas + despacho
  const r2 = await recipientsForEvent('order_cancelled');
  check(r2.includes('56911112222@c.us') && r2.includes('56933334444@c.us'), 'order_cancelled enruta a ventas y despacho');

  // 7. Fallback a admin cuando el rol destino no está configurado
  await pool.execute("DELETE FROM configuraciones WHERE clave = 'despacho_whatsapp_number'");
  const r3 = await recipientsForEvent('order_ready_for_dispatch');
  const adminJid = await getRoleJid('admin');
  check(r3.length === 1 && r3[0] === adminJid, 'sin despacho configurado, cae a admin');

  // 8. listRoleConfig devuelve los 3 roles
  const cfg = await listRoleConfig();
  check(ROLES.every(r => r in cfg), 'listRoleConfig incluye los 3 roles');

  // Restaurar config previa (o limpiar las claves de prueba)
  for (const r of ['ventas', 'despacho']) {
    if (previos[r]) await setRoleNumber(r, previos[r]);
    else await pool.execute(`DELETE FROM configuraciones WHERE clave = '${r}_whatsapp_number'`);
  }

  console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('ERROR:', e); process.exit(1); });
