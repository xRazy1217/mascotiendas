import { searchProducts, listCategories, checkDeliveryZone } from './tools.js';

async function test() {
  console.log('🧪 Probando conexión a base de datos y herramientas locales...\n');
  try {
    const cats = await listCategories();
    console.log('✅ Conexión establecida con éxito.');
    console.log(`📂 Categorías registradas (${cats.length}):`);
    console.table(cats.slice(0, 5));
    
    const products = await searchProducts('perro', 3);
    console.log(`\n📦 Productos que coinciden con "perro" (${products.length}):`);
    console.table(products);

    const zones = await checkDeliveryZone('Peñuelas');
    console.log('\n🚚 Consulta de zona "Peñuelas":');
    console.table(zones);

  } catch (error) {
    console.error('\n❌ ERROR DE CONEXIÓN O CONSULTA:');
    console.error(error.message);
    console.log('\nPor favor, verifica lo siguiente:');
    console.log('1. Que tu WAMP / MariaDB / MySQL esté encendido.');
    console.log('2. Que el puerto en el archivo .env sea correcto (actualmente 3308).');
    console.log('3. Que la base de datos se llame "mascotiendas" y tenga datos cargados.');
  }
  process.exit(0);
}

test();
