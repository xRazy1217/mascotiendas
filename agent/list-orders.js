import pool from './db.js';

async function listOrders() {
  try {
    const [rows] = await pool.execute(
      `SELECT id, nombre_cliente, telefono, total, estado, creado_en FROM pedidos LIMIT 10`
    );
    console.log('📦 Pedidos Registrados en la Base de Datos:\n');
    console.table(rows);
  } catch (error) {
    console.error('Error:', error.message);
  }
  process.exit(0);
}

listOrders();
