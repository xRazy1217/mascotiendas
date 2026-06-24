// Prueba de la persistencia del historial de conversación.
import { ensureHistoryTable, loadHistory, saveHistory, deleteHistory } from '../history-store.js';
import pool from '../db.js';

let pass = 0, fail = 0;
const check = (ok, msg) => { console.log(`  ${ok ? '✔' : '✘'} ${msg}`); ok ? pass++ : fail++; };

const CHAT = 'test_hist_99999@c.us';

async function main() {
  console.log('\n═══ TEST PERSISTENCIA DE HISTORIAL ═══\n');

  await ensureHistoryTable();
  check(true, 'ensureHistoryTable no revienta (idempotente)');

  // Chat nuevo => historial vacío
  await deleteHistory(CHAT);
  check((await loadHistory(CHAT)).length === 0, 'chat sin historial => arreglo vacío');

  // Guardar un historial con partes de texto y de herramienta (formato Gemini)
  const hist = [
    { role: 'user', parts: [{ text: 'hola, tienen comida?' }] },
    { role: 'model', parts: [{ functionCall: { name: 'searchProducts', args: { query: 'comida' } } }] },
    { role: 'tool', parts: [{ functionResponse: { name: 'searchProducts', response: { result: [{ id: 1, nombre: 'X' }] } } }] },
    { role: 'model', parts: [{ text: 'Sí, tenemos *X* a $1.000 🐾' }] }
  ];
  check(await saveHistory(CHAT, hist), 'saveHistory devuelve true');

  // Cargar y comparar (round-trip JSON)
  const loaded = await loadHistory(CHAT);
  check(loaded.length === 4, 'se cargan las 4 entradas');
  check(loaded[0].role === 'user' && loaded[0].parts[0].text === 'hola, tienen comida?', 'preserva el turno de usuario');
  check(loaded[1].parts[0].functionCall.name === 'searchProducts', 'preserva la llamada a herramienta');
  check(loaded[3].parts[0].text.includes('$1.000'), 'preserva la respuesta final del modelo');

  // Upsert: guardar uno más corto sobrescribe
  await saveHistory(CHAT, [{ role: 'user', parts: [{ text: 'nuevo' }] }]);
  const reloaded = await loadHistory(CHAT);
  check(reloaded.length === 1 && reloaded[0].parts[0].text === 'nuevo', 'upsert sobrescribe el historial');

  // Borrar
  await deleteHistory(CHAT);
  check((await loadHistory(CHAT)).length === 0, 'deleteHistory deja el chat sin historial');

  console.log(`\n═══ RESULTADO: ${pass} PASS / ${fail} FAIL ═══\n`);
  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('ERROR:', e); process.exit(1); });
