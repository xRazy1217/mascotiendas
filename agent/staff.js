// Lógica de staff: permisos por rol e interpretación de lenguaje natural para gestionar pedidos.
// Módulo puro (sin BD ni WhatsApp) para poder testearlo de forma aislada.

// Qué estados puede fijar cada rol y qué comandos puede ejecutar.
export const ROLE_PERMISSIONS = {
  admin: {
    estados: ['pendiente', 'pagado', 'preparando', 'enviado', 'entregado', 'cancelado'],
    commands: ['!resumen', '!reporte', '!pendientes', '!estado', '!roles', '!setrol', '!ayuda', '!comandos']
  },
  ventas: {
    // Ventas confirma el pago por transferencia y puede anular
    estados: ['pagado', 'cancelado'],
    commands: ['!resumen', '!reporte', '!pendientes', '!estado', '!ayuda', '!comandos']
  },
  despacho: {
    // Despacho mueve la operación física (cobra efectivo/tarjeta al entregar)
    estados: ['preparando', 'enviado', 'entregado'],
    commands: ['!pendientes', '!estado', '!ayuda', '!comandos']
  }
};

export function canRunCommand(role, cmd) {
  const p = ROLE_PERMISSIONS[role];
  return !!p && p.commands.includes(cmd);
}

export function canSetEstado(role, estado) {
  const p = ROLE_PERMISSIONS[role];
  return !!p && p.estados.includes(String(estado || '').toLowerCase());
}

const norm = (t) => String(t || '').toLowerCase().trim();

// Fin de palabra tolerante a acentos/fin de cadena (el \b ASCII falla tras "í", "é", etc.)
const END = '(?=$|\\s|[.,!¡¿?])';

/** ¿El mensaje es una confirmación afirmativa? */
export function isAffirmation(text) {
  return new RegExp(`^(s[ií]|ya po|ya|dale|confirmo?|correcto|ok|oka?y|hazlo|apl[ií]ca(lo)?|listo|exacto|as[ií] es|de una)${END}`, 'i').test(norm(text));
}

/** ¿El mensaje es una negación / cancelación de la confirmación? */
export function isNegation(text) {
  return new RegExp(`^(no|cancela|negativo|mejor no|espera|para|det[eé]n)${END}`, 'i').test(norm(text));
}

// Mapa de palabras clave -> estado destino (en orden de prioridad)
const ESTADO_KEYWORDS = [
  ['entregado', /\b(entregad|entregu[eé]|lleg[oó]|recibi[oó]|ya lleg|fue entregad)/],
  ['enviado', /\b(sal[ií]|en camino|despach[ée]?|voy en camino|reparti|ya va|en ruta)/],
  ['preparando', /\b(prepar|arm(ando|[ée]|ar)|empaqu|listo para salir|alistand)/],
  ['pagado', /\b(pag[oó]|transferenci|abon[oó]|deposit|ya transfir)/],
  ['cancelado', /\b(anul|cancel)/]
];

/**
 * Interpreta un mensaje de staff en lenguaje natural.
 * @returns {object} { action: 'set_status'|'need_id'|'list'|'summary'|'unknown', orderId?, estado? }
 */
export function parseStaffIntent(text) {
  const t = norm(text);

  // ID de pedido: priorizar el que viene junto a "#", "pedido" u "orden"; si no, el primer número suelto
  let orderId = null;
  const m1 = t.match(/(?:#|pedido|orden|n[°º.]?)\s*(\d{1,6})/);
  if (m1) orderId = Number(m1[1]);
  else {
    const m2 = t.match(/\b(\d{1,6})\b/);
    if (m2) orderId = Number(m2[1]);
  }

  // Estado por palabras clave
  let estado = null;
  for (const [name, re] of ESTADO_KEYWORDS) {
    if (re.test(t)) { estado = name; break; }
  }

  // Intenciones de consulta (solo si no hay cambio de estado)
  if (!estado) {
    if (/\b(pendientes|qu[eé] hay|qu[eé] tengo|qu[eé] queda|la lista|los pedidos)\b/.test(t)) return { action: 'list' };
    if (/\b(resumen|ventas del d[ií]a|c[oó]mo va(mos)? hoy|reporte)\b/.test(t)) return { action: 'summary' };
  }

  if (estado && orderId) return { action: 'set_status', orderId, estado };
  if (estado && !orderId) return { action: 'need_id', estado };
  return { action: 'unknown' };
}
