import pool from './db.js';

/**
 * Lee un valor de la tabla configuraciones (clave/valor).
 * @param {string} clave
 * @returns {Promise<string|null>} El valor, o null si no existe o hay error.
 */
export async function getConfig(clave) {
  try {
    const [rows] = await pool.execute('SELECT valor FROM configuraciones WHERE clave = ?', [clave]);
    return rows.length > 0 ? rows[0].valor : null;
  } catch (err) {
    console.error(`[config] Error leyendo '${clave}':`, err.message);
    return null;
  }
}

/**
 * Inserta o actualiza un valor en configuraciones (upsert por clave).
 * @param {string} clave
 * @param {string|number} valor
 * @returns {Promise<boolean>} true si se guardó, false si hubo error.
 */
export async function setConfig(clave, valor) {
  try {
    await pool.execute(
      'INSERT INTO configuraciones (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?',
      [clave, String(valor), String(valor)]
    );
    return true;
  } catch (err) {
    console.error(`[config] Error guardando '${clave}':`, err.message);
    return false;
  }
}
