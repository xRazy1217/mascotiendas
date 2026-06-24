// Prueba de regresión del resumen diario de ventas.
import { getDailySalesSummary, formatDailyReport } from '../reports.js';
import pool from '../db.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

async function main() {
  console.log('\n═══ TEST RESUMEN DIARIO ═══\n');

  // Día más activo histórico para validar con datos reales
  const [dias] = await pool.execute('SELECT DATE(creado_en) d, COUNT(*) n FROM pedidos GROUP BY DATE(creado_en) ORDER BY n DESC LIMIT 1');
  const busy = dias[0].d instanceof Date ? dias[0].d.toISOString().slice(0, 10) : String(dias[0].d).slice(0, 10);
  console.log(`Día de prueba: ${busy}`);

  const s = await getDailySalesSummary(busy);

  check(s.ventas.pedidos > 0, 'hay pedidos en el día de prueba');
  check(s.ventas.total >= s.ventas.subtotal - s.ventas.descuento, 'total coherente con subtotal/descuento');
  check(s.clientes.nuevos + s.clientes.recurrentes === s.clientes.total, 'nuevos + recurrentes = total de clientes');
  check(typeof s.incompletosHoy === 'number' && s.incompletosHoy >= 0, 'incompletos del día es numérico');
  check(s.incompletosTotal >= s.incompletosHoy, 'backlog total >= incompletos del día');
  check(Array.isArray(s.topProductos) && s.topProductos.length <= 5, 'top productos <= 5');

  const txt = formatDailyReport(s);
  check(txt.includes('RESUMEN DIARIO'), 'el texto tiene encabezado');
  check(txt.includes('VENTAS DEL DÍA') && txt.includes('CLIENTES') && txt.includes('INCOMPLETOS'), 'tiene las secciones clave');
  check(!/NaN|undefined|\$\s|null/.test(txt), 'sin NaN/undefined/null en el texto');

  // Día sin datos: no debe reventar
  const empty = await getDailySalesSummary('2000-01-01');
  const txt2 = formatDailyReport(empty);
  check(empty.ventas.pedidos === 0 && txt2.includes('RESUMEN DIARIO'), 'día vacío genera reporte válido sin error');

  console.log(`\n── Vista previa del reporte (${busy}) ──`);
  console.log(txt);

  console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('ERROR:', e); process.exit(1); });
