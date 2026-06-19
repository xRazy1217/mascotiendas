import pool from './db.js';

async function listSome() {
  try {
    const [rows] = await pool.execute(
      `SELECT p.id, p.nombre, p.precio_normal, p.en_stock,
              (SELECT GROUP_CONCAT(c.nombre SEPARATOR ', ') FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id) AS categorias
       FROM productos p 
       WHERE p.activo = 1 
       ORDER BY RAND() 
       LIMIT 10`
    );
    console.log('📦 10 Productos Aleatorios en la Base de Datos:\n');
    console.table(rows);
  } catch (error) {
    console.error('Error:', error.message);
  }
  process.exit(0);
}

listSome();
