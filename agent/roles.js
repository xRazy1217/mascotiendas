import { getConfig, setConfig } from './config-store.js';

// Roles del equipo. El admin siempre existe (con fallback); ventas y despacho se configuran.
export const ROLES = ['admin', 'ventas', 'despacho'];

const CONFIG_KEY = {
  admin: 'admin_whatsapp_number',
  ventas: 'ventas_whatsapp_number',
  despacho: 'despacho_whatsapp_number'
};

// Fallback histórico del admin para no quedar nunca sin destinatario
const ADMIN_FALLBACK = '56920571475';

const toDigits = (v) => String(v || '').replace(/\D/g, '');

// Caché en memoria de los números por rol: resolveRole se llama en cada mensaje entrante,
// así evitamos 3 lecturas a la BD por mensaje. Se invalida al cambiar un número.
let _numCache = null;
let _numCacheAt = 0;
const NUM_CACHE_TTL_MS = 60 * 1000;

/** Invalida la caché de números por rol (tras un cambio de configuración). */
export function invalidateRoleCache() {
  _numCache = null;
}

async function allRoleNumbers() {
  if (_numCache && Date.now() - _numCacheAt < NUM_CACHE_TTL_MS) return _numCache;
  const m = {};
  for (const r of ROLES) {
    const d = toDigits(await getConfig(CONFIG_KEY[r]));
    m[r] = d || (r === 'admin' ? ADMIN_FALLBACK : null);
  }
  _numCache = m;
  _numCacheAt = Date.now();
  return m;
}

/** Devuelve el número (solo dígitos) configurado para un rol, o null si no hay. */
export async function getRoleNumber(role) {
  if (!CONFIG_KEY[role]) return null;
  const m = await allRoleNumbers();
  return m[role] || null;
}

/** Devuelve el JID de WhatsApp (569...@c.us) de un rol, o null. */
export async function getRoleJid(role) {
  const d = await getRoleNumber(role);
  return d ? `${d}@c.us` : null;
}

/** Configura el número de un rol (validando formato chileno básico). */
export async function setRoleNumber(role, numero) {
  if (!CONFIG_KEY[role]) {
    return { success: false, message: `Rol inválido. Usa: ${ROLES.join(', ')}.` };
  }
  const d = toDigits(numero);
  if (d.length < 9 || d.length > 12) {
    return { success: false, message: 'Número inválido. Usa formato 569XXXXXXXX.' };
  }
  await setConfig(CONFIG_KEY[role], d);
  invalidateRoleCache();
  return { success: true, message: `✅ Número de *${role}* actualizado a +${d}.`, role, numero: d };
}

/** Devuelve { admin, ventas, despacho } con sus números (o null si no configurado). */
export async function listRoleConfig() {
  const out = {};
  for (const r of ROLES) out[r] = await getRoleNumber(r);
  return out;
}

/** Identifica el rol de un teléfono entrante por sus últimos 9 dígitos, o null. */
export async function resolveRole(phone) {
  const last9 = toDigits(phone).slice(-9);
  if (!last9) return null;
  for (const r of ROLES) {
    const num = await getRoleNumber(r);
    if (num && num.slice(-9) === last9) return r;
  }
  return null;
}

// Ruteo de notificaciones por tipo de evento. Si el rol destino no está configurado,
// recipientsForEvent cae a admin para no perder el aviso.
export const EVENT_ROUTING = {
  order_created: ['ventas'],
  order_handoff: ['ventas'],
  order_updated: ['despacho'],
  order_ready_for_dispatch: ['despacho'],
  order_cancelled: ['ventas', 'despacho'],
  daily_report: ['admin'],
  bot_health: ['admin']
};

/** Devuelve los JIDs destinatarios (sin duplicados) para un evento, con fallback a admin. */
export async function recipientsForEvent(eventType) {
  const roles = EVENT_ROUTING[eventType] || ['admin'];
  const jids = new Set();
  for (const role of roles) {
    let jid = await getRoleJid(role);
    if (!jid) jid = await getRoleJid('admin'); // fallback: nunca perder el aviso
    if (jid) jids.add(jid);
  }
  return [...jids];
}
