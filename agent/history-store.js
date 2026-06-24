import pool from './db.js';

// Persiste el historial de conversación de cada chat para que sobreviva a reinicios del bot.
// El historial es el arreglo de mensajes en formato Gemini ([{ role, parts }]), guardado como JSON.

let _ready = false;

/** Crea la tabla bot_historial si no existe (idempotente). */
export async function ensureHistoryTable() {
  if (_ready) return;
  await pool.execute(
    `CREATE TABLE IF NOT EXISTS bot_historial (
       chat_id VARCHAR(64) NOT NULL PRIMARY KEY,
       historial LONGTEXT NOT NULL,
       actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
  );
  _ready = true;
}

/** Carga el historial de un chat desde la BD (arreglo vacío si no hay o si falla). */
export async function loadHistory(chatId) {
  try {
    const [rows] = await pool.execute('SELECT historial FROM bot_historial WHERE chat_id = ?', [chatId]);
    if (rows.length === 0) return [];
    const arr = JSON.parse(rows[0].historial);
    return Array.isArray(arr) ? arr : [];
  } catch (e) {
    console.error(`[Historial] Error cargando ${chatId}:`, e.message);
    return [];
  }
}

/** Guarda (upsert) el historial de un chat. */
export async function saveHistory(chatId, historyArr) {
  try {
    const json = JSON.stringify(historyArr || []);
    await pool.execute(
      'INSERT INTO bot_historial (chat_id, historial) VALUES (?, ?) ON DUPLICATE KEY UPDATE historial = ?',
      [chatId, json, json]
    );
    return true;
  } catch (e) {
    console.error(`[Historial] Error guardando ${chatId}:`, e.message);
    return false;
  }
}

/** Borra el historial de un chat (para el comando !reiniciar). */
export async function deleteHistory(chatId) {
  try {
    await pool.execute('DELETE FROM bot_historial WHERE chat_id = ?', [chatId]);
    return true;
  } catch (e) {
    console.error(`[Historial] Error borrando ${chatId}:`, e.message);
    return false;
  }
}

/** Limpia historiales no tocados en los últimos N días (acota el crecimiento de la tabla). */
export async function purgeOldHistory(days = 30) {
  try {
    const [r] = await pool.execute(
      'DELETE FROM bot_historial WHERE actualizado_en < (NOW() - INTERVAL ? DAY)', [days]
    );
    return r.affectedRows || 0;
  } catch (e) {
    console.error('[Historial] Error purgando antiguos:', e.message);
    return 0;
  }
}
