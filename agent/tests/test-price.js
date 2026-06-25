function fixPrice(t) {
  // 1. Quitar " pesos" sobrante cuando ya viene con $
  t = t.replace(/(\$\d{1,3}(?:\.\d{3})+)\s+pesos\b/gi, '$1');
  // 2. Anteponer $ a "N.NNN pesos" sin $, preservando el espacio previo
  t = t.replace(/(?<!\$)\b(\d{1,3}(?:\.\d{3})+)\s+pesos\b/gi, '$$$1');
  return t;
}

const cases = [
  'Nos queda a 47.900 pesos. ¿Te lo guardo?',
  'Cuesta $47.900 pesos en total.',
  'El saco está a $42.900 y nos queda en stock.',
  'Son 1.234.567 pesos por el combo.',
  'Te lo dejo en $28.900, ¿confirmas?',
  'Ese vale 9.990 pesos no más.'
];
for (const c of cases) console.log(c + '\n  -> ' + fixPrice(c) + '\n');
