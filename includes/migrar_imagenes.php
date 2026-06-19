<?php
/**
 * Script de Migración de Imágenes Remotas a Local con Respaldo
 * Ejecutar vía terminal: php -f includes/migrar_imagenes.php
 */

require_once __DIR__ . '/funciones.php';
$pdo = getPDO();

$baseDir = dirname(__DIR__);
$uploadsProdDir = $baseDir . '/uploads/productos';
$uploadsTextDir = $baseDir . '/uploads/textos';
$backupFile = $baseDir . '/uploads/backup_urls_remotos.json';

echo "=== INICIANDO MIGRACION DE IMAGENES ===\n";

// 1. Crear directorios si no existen
if (!file_exists($baseDir . '/uploads')) {
    mkdir($baseDir . '/uploads', 0777, true);
}
if (!file_exists($uploadsProdDir)) {
    mkdir($uploadsProdDir, 0777, true);
    echo "Directorio creado: $uploadsProdDir\n";
}
if (!file_exists($uploadsTextDir)) {
    mkdir($uploadsTextDir, 0777, true);
    echo "Directorio creado: $uploadsTextDir\n";
}

// 2. Obtener datos para el respaldo
echo "Generando respaldo de enlaces remotos...\n";

$stmtProd = $pdo->query("SELECT id, producto_id, url, posicion FROM producto_imagenes WHERE url LIKE '%mascotiendas.cl/wp-content/uploads/%'");
$remoteImgs = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

$stmtTxt = $pdo->query("SELECT clave, valor FROM textos WHERE valor LIKE '%mascotiendas.cl/wp-content/uploads/%'");
$remoteTxts = $stmtTxt->fetchAll(PDO::FETCH_ASSOC);

$backupData = [
    'fecha_migracion' => date('Y-m-d H:i:s'),
    'producto_imagenes' => $remoteImgs,
    'textos' => $remoteTxts
];

file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Respaldo creado en: $backupFile (" . (count($remoteImgs) + count($remoteTxts)) . " registros respaldados)\n\n";

// Función auxiliar para descargar vía cURL
function descargarArchivo($url, $destPath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // Ignorar errores de SSL en local por si acaso
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$data) {
        return false;
    }

    return file_put_contents($destPath, $data) !== false;
}

$exitos = 0;
$fallas = [];

// 3. Descargar y actualizar producto_imagenes
echo "Procesando imágenes de productos...\n";
foreach ($remoteImgs as $index => $img) {
    $id = $img['id'];
    $prodId = $img['producto_id'];
    $urlRemota = $img['url'];
    
    // Obtener nombre del archivo original
    $filename = basename(parse_url($urlRemota, PHP_URL_PATH));
    
    // Si por alguna razón el nombre de archivo está vacío, generar uno
    if (empty($filename)) {
        $filename = "producto_{$prodId}_{$img['posicion']}.jpg";
    }

    $destPath = $uploadsProdDir . '/' . $filename;
    $urlLocal = '/uploads/productos/' . $filename;

    echo "Descargando [" . ($index + 1) . "/" . count($remoteImgs) . "] (ID: $id, Prod: $prodId): $filename ... ";

    if (descargarArchivo($urlRemota, $destPath)) {
        // Actualizar referencia en BD
        $update = $pdo->prepare("UPDATE producto_imagenes SET url = ? WHERE id = ?");
        $update->execute([$urlLocal, $id]);
        echo "[OK]\n";
        $exitos++;
    } else {
        echo "[ERROR]\n";
        $fallas[] = [
            'tipo' => 'producto_imagen',
            'id' => $id,
            'producto_id' => $prodId,
            'url' => $urlRemota
        ];
    }
}

// 4. Descargar y actualizar textos
echo "\nProcesando textos de configuración...\n";
foreach ($remoteTxts as $index => $txt) {
    $clave = $txt['clave'];
    $urlRemota = $txt['valor'];

    $filename = basename(parse_url($urlRemota, PHP_URL_PATH));
    if (empty($filename)) {
        $filename = "texto_{$clave}.jpg";
    }

    $destPath = $uploadsTextDir . '/' . $filename;
    $urlLocal = '/uploads/textos/' . $filename;

    echo "Descargando texto ($clave): $filename ... ";

    if (descargarArchivo($urlRemota, $destPath)) {
        // Actualizar referencia en BD
        $update = $pdo->prepare("UPDATE textos SET valor = ? WHERE clave = ?");
        $update->execute([$urlLocal, $clave]);
        echo "[OK]\n";
        $exitos++;
    } else {
        echo "[ERROR]\n";
        $fallas[] = [
            'tipo' => 'texto',
            'clave' => $clave,
            'url' => $urlRemota
        ];
    }
}

// 5. Reporte Final
echo "\n=== REPORTE FINAL DE MIGRACION ===\n";
echo "Total descargas exitosas y actualizadas: $exitos\n";
echo "Total descargas fallidas: " . count($fallas) . "\n";

if (count($fallas) > 0) {
    echo "\nLista de descargas fallidas:\n";
    foreach ($fallas as $f) {
        if ($f['tipo'] === 'producto_imagen') {
            echo "- Imagen ID: {$f['id']} (Producto ID: {$f['producto_id']}): {$f['url']}\n";
        } else {
            echo "- Texto Clave: {$f['clave']}: {$f['url']}\n";
        }
    }
} else {
    echo "\n¡Todas las imágenes fueron migradas con éxito!\n";
}
