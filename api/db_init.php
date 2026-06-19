<?php
/**
 * MASCOTIENDAS - Inicializador de Base de Datos
 * ---------------------------------------------
 * Ejecuta los esquemas SQL en el orden correcto de dependencias.
 */

require_once __DIR__ . '/../config/db.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado. Este script solo puede ejecutarse mediante línea de comandos (CLI).'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Habilitar mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Crear base de datos si no existe (conexión temporal sin DB name)
try {
    $tempPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<h3>✅ Base de datos '" . DB_NAME . "' creada o ya existente.</h3>";
} catch (PDOException $e) {
    die("<h3 style='color:red;'>❌ Error al inicializar/conectar al servidor MySQL: " . $e->getMessage() . "</h3>");
}

// 2. Conectar a la DB e importar esquemas en orden
$pdo = getPDO();

$sqlFiles = [
    'Productos y Categorías'  => __DIR__ . '/../ayuda/mascotiendas_productos.sql',
    'Esquema Admin & Cupones' => __DIR__ . '/../config/schema_admin.sql',
    'Esquema Extra (Usuarios)' => __DIR__ . '/../config/schema_extra.sql',
    'Esquema v3 (SEO & Blog)'  => __DIR__ . '/../config/schema_v3.sql',
    'Esquema v4 (Reviews/FOMO)'=> __DIR__ . '/../config/schema_v4.sql',
    'Esquema Variantes'       => __DIR__ . '/../config/schema_variantes.sql',
    'Esquema Configuraciones' => __DIR__ . '/../config/schema_configuraciones.sql',
    'Esquema Textos & Campañas'=> __DIR__ . '/../config/schema_textos_campanas.sql',
    'Esquema Marketing'       => __DIR__ . '/../config/schema_marketing.sql'
];

foreach ($sqlFiles as $nombre => $ruta) {
    if (!file_exists($ruta)) {
        echo "<p style='color:orange;'>⚠️ Archivo no encontrado para '$nombre': <code>$ruta</code>. Saltando...</p>";
        continue;
    }

    try {
        echo "<p>Cargando <strong>$nombre</strong> (<code>" . basename($ruta) . "</code>)... ";
        $sql = file_get_contents($ruta);
        
        // Ejecutar multi-consulta
        $pdo->exec($sql);
        echo "<span style='color:green;font-weight:bold;'>✓ Importado con éxito.</span></p>";
    } catch (PDOException $e) {
        echo "<span style='color:red;font-weight:bold;'>✗ ERROR:</span> " . $e->getMessage() . "</p>";
    }
}

echo "<h3>🎉 Proceso de inicialización completado.</h3>";
echo "<p>Puedes iniciar sesión en el panel <a href='../admin/'>/admin/</a> con:</p>";
echo "<ul><li><strong>Email:</strong> admin@mascotiendas.cl</li><li><strong>Contraseña:</strong> Admin1234!</li></ul>";
