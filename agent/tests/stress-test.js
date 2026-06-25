// Stress-test multi-escenario para el bot Max (Mascotiendas)
// Ejecuta conversaciones aisladas, extrae las herramientas llamadas desde el historial
// y corre aserciones automáticas. No confirma compras para no ensuciar la BD (salvo donde se indique).
import { runAgent } from '../agent.js';
import pool from '../db.js';

const C = { g: '\x1b[32m', r: '\x1b[31m', y: '\x1b[33m', c: '\x1b[36m', d: '\x1b[2m', x: '\x1b[0m' };
const FORBIDDEN = ['weón', 'hueón', 'weon', 'hueon', 'chucha', 'xuxa', 'raja', 'conchatumare', 'ctm'];
const BOT_WORDS = ['soy un bot', 'soy una ia', 'modelo de lenguaje', 'inteligencia artificial', 'soy un chatbot', 'asistente virtual'];

// Extrae los nombres de herramientas llamadas a lo largo de un historial de Gemini
function toolsFrom(history) {
  const names = [];
  for (const m of history) {
    if (m.role === 'model' && Array.isArray(m.parts)) {
      for (const p of m.parts) if (p.functionCall) names.push(p.functionCall.name);
    }
  }
  return names;
}

const wc = (s) => (s || '').trim().split(/\s+/).filter(Boolean).length;
const has = (s, sub) => (s || '').toLowerCase().includes(sub.toLowerCase());
const hasAny = (s, arr) => arr.some(w => has(s, w));

let pass = 0, fail = 0;
const results = [];

// Corre una secuencia de mensajes del cliente y aplica una función de aserción
async function scenario(name, phone, turns, assertFn) {
  let history = [];
  const log = [];
  let lastAnswer = '', allTools = [];
  try {
    for (const userMsg of turns) {
      const res = await runAgent(history, userMsg, phone);
      history = res.history;
      lastAnswer = res.answer;
      allTools = toolsFrom(history);
      log.push({ user: userMsg, bot: res.answer });
    }
    const checks = assertFn({ answer: lastAnswer, tools: allTools, history });
    const ok = checks.every(c => c.ok);
    results.push({ name, ok, checks, log });
    if (ok) pass++; else fail++;
  } catch (e) {
    results.push({ name, ok: false, checks: [{ ok: false, msg: 'EXCEPCIÓN: ' + e.message }], log });
    fail++;
  }
}

async function main() {
  console.log(`\n${C.c}═══ STRESS-TEST MULTI-ESCENARIO — BOT "MAX" ═══${C.x}\n`);

  // 1. Identidad: debe presentarse como Max, nunca admitir ser IA
  await scenario('1. Identidad (¿quién eres?)', '56911111111',
    ['hola, con quien hablo? eres un robot?'],
    ({ answer }) => [
      { ok: has(answer, 'max'), msg: 'se presenta como Max' },
      { ok: !hasAny(answer, BOT_WORDS), msg: 'NO admite ser bot/IA' },
    ]);

  // 2. Consulta genérica: no debe responder vacío, debe aclarar
  await scenario('2. Consulta genérica (anti-vacío)', '56922222222',
    ['tienen comida para perro?'],
    ({ answer }) => [
      { ok: !!answer.trim(), msg: 'respuesta NO vacía' },
      { ok: answer.includes('?'), msg: 'hace pregunta de aclaración' },
    ]);

  // 3. Precio real: debe consultar herramienta, no inventar
  await scenario('3. Precio real (usa herramienta)', '56933333333',
    ['cuanto cuesta el dockennedy adulto 15 kilos?'],
    ({ answer, tools }) => [
      { ok: tools.includes('searchProducts') || tools.includes('getProductDetails'), msg: 'llama searchProducts/getProductDetails' },
      { ok: /\$\s?\d/.test(answer), msg: 'entrega un precio concreto' },
    ]);

  // 4. Mascota enferma: empatía + derivación veterinaria (reglas #15/#16)
  await scenario('4. Mascota enferma (empatía + vet)', '56944444444',
    ['hola, mi perrito esta vomitando hace 2 dias y no quiere comer, que le doy?'],
    ({ answer }) => [
      { ok: has(answer, 'veterinari'), msg: 'deriva al veterinario' },
      { ok: !/\$\s?\d/.test(answer), msg: 'NO empuja venta con precio en momento sensible' },
    ]);

  // 5. Producto inexistente: honestidad / alternativa, sin inventar
  await scenario('5. Marca inexistente (no inventar)', '56955555555',
    ['tienen alimento de la marca SuperMegaPremiumXYZ123?'],
    ({ answer, tools }) => [
      { ok: tools.includes('searchProducts'), msg: 'busca antes de responder' },
      { ok: !!answer.trim(), msg: 'responde algo (no vacío)' },
    ]);

  // 6. Historial de pedidos: usa getClientOrders con teléfono del contexto
  await scenario('6. Historial (¿cómo va mi pedido?)', '56949865594',
    ['hola, como va mi ultimo pedido?'],
    ({ tools }) => [
      { ok: tools.includes('getClientOrders'), msg: 'llama getClientOrders' },
    ]);

  // 7. Anulación sin pedidos: maneja el caso vacío con gracia (teléfono sin órdenes)
  await scenario('7. Anulación sin pedido pendiente', '56900000001',
    ['quiero anular mi pedido por favor'],
    ({ answer, tools }) => [
      { ok: tools.includes('cancelClientOrder'), msg: 'intenta cancelClientOrder' },
      { ok: !!answer.trim(), msg: 'informa resultado (no vacío)' },
    ]);

  // 8. Validación de horario pasado (2 turnos)
  await scenario('8. Horario pasado (validación)', '56949865594',
    ['quiero el fit formula senior mediana-grande 20 kgs',
     'mandenmelo hoy a las 7 de la mañana porfa'],
    ({ answer }) => [
      { ok: has(answer, 'pas') || has(answer, 'mañana') || has(answer, 'más tarde') || has(answer,'tarde'), msg: 'detecta/ofrece reagendar horario pasado' },
    ]);

  // 9. Anti-vulgar: cliente grosero, el bot mantiene el tono
  await scenario('9. Anti-vulgaridad (tono)', '56977777777',
    ['ya po weón apúrate, tenís comida pa gato o no?'],
    ({ answer }) => [
      { ok: !hasAny(answer, FORBIDDEN), msg: 'NO usa groserías' },
      { ok: !!answer.trim(), msg: 'responde igual' },
    ]);

  // 10. Despacho/zona: consulta cobertura
  await scenario('10. Cobertura de despacho', '56988888888',
    ['hacen despacho a Las Compañías?'],
    ({ tools, answer }) => [
      { ok: tools.includes('checkDeliveryZone') || tools.includes('readWikiPage'), msg: 'consulta zona/wiki' },
      { ok: !!answer.trim(), msg: 'responde (no vacío)' },
    ]);

  // 11. Reclamo serio: debe derivar a un ejecutivo humano
  await scenario('11. Reclamo (deriva a humano)', '56949865594',
    ['me llego el saco roto y todo botado, esto es un desastre, exijo un reembolso ya'],
    ({ tools, answer }) => [
      { ok: tools.includes('escalateToHuman'), msg: 'llama escalateToHuman' },
      { ok: !!answer.trim(), msg: 'avisa al cliente (no vacío)' },
    ]);

  // 12. Consulta normal: NO debe derivar a humano
  await scenario('12. Consulta normal (NO deriva)', '56912121212',
    ['tienen arena para gato?'],
    ({ tools }) => [
      { ok: !tools.includes('escalateToHuman'), msg: 'NO escala una consulta normal' },
    ]);

  // ── Reporte ──
  console.log(`${C.c}──────── TRANSCRIPCIONES ────────${C.x}`);
  for (const res of results) {
    const tag = res.ok ? `${C.g}PASS${C.x}` : `${C.r}FAIL${C.x}`;
    console.log(`\n[${tag}] ${res.name}`);
    for (const t of res.log) {
      console.log(`  ${C.c}👤${C.x} ${t.user}`);
      console.log(`  ${C.g}🐾 [${wc(t.bot)} palabras]${C.x} ${t.bot.replace(/\n/g, ' ')}`);
    }
    for (const c of res.checks) {
      console.log(`     ${c.ok ? C.g + '✔' : C.r + '✘'}${C.x} ${c.msg}`);
    }
  }

  // Chequeo global de brevedad
  const allBotMsgs = results.flatMap(r => r.log.map(l => l.bot));
  const longOnes = allBotMsgs.filter(b => wc(b) > 45);
  console.log(`\n${C.c}──────── BREVEDAD GLOBAL ────────${C.x}`);
  console.log(`  Mensajes totales del bot: ${allBotMsgs.length} | Promedio: ${(allBotMsgs.reduce((a, b) => a + wc(b), 0) / allBotMsgs.length).toFixed(1)} palabras`);
  console.log(`  ${longOnes.length === 0 ? C.g + '✔' : C.y + '⚠'} ${C.x}Mensajes >45 palabras: ${longOnes.length}`);

  console.log(`\n${C.c}════════ RESUMEN ════════${C.x}`);
  console.log(`  ${C.g}PASS: ${pass}${C.x}   ${C.r}FAIL: ${fail}${C.x}   Total: ${pass + fail}\n`);

  await pool.end();
  process.exit(fail > 0 ? 1 : 0);
}

main();
