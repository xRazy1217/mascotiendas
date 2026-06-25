function sanitizeAnswer(text) {
  if (!text) return text;
  let t = text;
  // 1. Quitar fugas de proceso interno
  t = t.replace(/\s*(en|seg[uú]n|desde)\s+(mi|el|la|nuestra?)\s+(sistema|base de datos|cat[aá]logo interno)/gi, '');
  // 2. Proteger autopresentacion "soy/llamo Max" con un token sin espacios
  t = t.replace(/\b(soy|llamo)\s+Max\b/gi, m => m.replace(/Max/i, 'MAXSELF'));
  // 3. Quitar "Max" usado como vocativo hacia el cliente
  t = t.replace(/\s*,\s*Max\b/g, '');
  t = t.replace(/\bMax\s*,\s*/g, '');
  // 4. Restaurar el token protegido y limpiar espacios/puntuacion
  t = t.replace(/MAXSELF/g, 'Max');
  t = t.replace(/\s{2,}/g, ' ').replace(/\s+([.,;:!?])/g, '$1').trim();
  return t;
}

const cases = [
  'Chuta, de la marca SuperMegaPremiumXYZ123 no nos aparece nada por acá, Max. ¿Te tinca si te ayudo a buscar alguna alternativa similar para tu regalón? 🐾',
  '¡Hola! Soy Max, vendedor de Mascotiendas aquí en La Serena. ¿En qué te puedo ayudar hoy con tu regalón? 🐾',
  'Chuta, no me apareció comida para gato en mi sistema justo ahora; ¿buscas alguna marca específica para revisar o para qué edad es tu regalón? 🐾',
  'Soy Max, listo para ayudarte.',
  'Veo que tu pedido está en la base de datos pendiente.',
  'Listo, Max, te lo agendo al tiro.'
];

for (const c of cases) {
  console.log('IN : ' + c);
  console.log('OUT: ' + sanitizeAnswer(c));
  console.log();
}
