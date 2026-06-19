import pool from './db.js';

async function main() {
  try {
    const [rows] = await pool.execute('SELECT * FROM zonas_delivery');
    console.log('🚚 Zonas de Delivery en MySQL:');
    console.table(rows);
  } catch (err) {
    console.error(err);
  } finally {
    process.exit(0);
  }
}
main();
