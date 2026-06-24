// Prueba del almacenamiento de configuración y la lógica de detección de caída del bot.
import { getConfig, setConfig } from '../config-store.js';
import pool from '../db.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

// Réplica de la decisión que toma index.js al reconectar
const DOWNTIME_ALERT_MINUTES = 5;
function shouldAlert(lastHeartbeatMs, nowMs) {
  if (!lastHeartbeatMs) return { alert: false, gapMin: 0 };
  const gapMin = Math.round((nowMs - Number(lastHeartbeatMs)) / 60000);
  return { alert: gapMin >= DOWNTIME_ALERT_MINUTES, gapMin };
}

async function main() {
  console.log('\n═══ TEST SALUD / UPTIME ═══\n');

  // 1. setConfig/getConfig round-trip
  const okSet = await setConfig('test_health_key', 'hola-123');
  const val = await getConfig('test_health_key');
  check(okSet && val === 'hola-123', 'setConfig/getConfig round-trip');

  // 2. upsert: sobrescribe el valor existente
  await setConfig('test_health_key', '456');
  check((await getConfig('test_health_key')) === '456', 'upsert sobrescribe el valor');

  // 3. clave inexistente -> null
  check((await getConfig('clave_que_no_existe_xyz')) === null, 'clave inexistente devuelve null');

  // 4. Heartbeat reciente (1 min) -> NO alerta
  const r1 = shouldAlert(Date.now() - 1 * 60000, Date.now());
  check(!r1.alert, `1 min sin latido NO alerta (gap=${r1.gapMin})`);

  // 5. Caída larga (12 min) -> SÍ alerta y calcula bien el gap
  const r2 = shouldAlert(Date.now() - 12 * 60000, Date.now());
  check(r2.alert && r2.gapMin === 12, `12 min sin latido SÍ alerta (gap=${r2.gapMin})`);

  // 6. Primer arranque sin latido previo -> NO alerta (evita falso positivo)
  const r3 = shouldAlert(null, Date.now());
  check(!r3.alert, 'sin latido previo (primer arranque) NO alerta');

  // 7. Persistencia real del heartbeat
  const t = Date.now();
  await setConfig('bot_last_heartbeat', t);
  check(Number(await getConfig('bot_last_heartbeat')) === t, 'bot_last_heartbeat persiste el timestamp');

  // Limpieza de la clave de prueba
  await pool.execute("DELETE FROM configuraciones WHERE clave = 'test_health_key'");

  console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('ERROR:', e); process.exit(1); });
