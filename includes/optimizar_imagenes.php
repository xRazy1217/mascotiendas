<?php
/**
 * Script CLI para la optimización de imágenes en Mascotiendas.
 * 
 * Convierte imágenes PNG/JPG a WebP, las redimensiona a un máximo de 800px
 * de ancho/alto y las comprime al 80% de calidad.
 * Actualiza las referencias correspondientes en la base de datos y realiza
 * respaldos de seguridad automáticos.
 * 
 * Uso:
 *   Simulación: php includes/optimizar_imagenes.php --dry-run
 *   Ejecución: php includes/optimizar_imagenes.php
 */

require_once __DIR__ . '/../config/db.php';

// ─── Configuración ───────────────────────────────────────────────────────────
define('BASE_PATH', str_replace('\\', '/', realpath(__DIR__ . '/..')));
define('BACKUP_DIR', BASE_PATH . '/uploads/backup_originales');
define('MAX_DIMENSION', 800);
define('WEBP_QUALITY', 80);

$dryRun = in_array('--dry-run', $argv);

echo "====================================================\n";
echo "       OPTIMIZADOR DE IMÁGENES MASCOTIENDAS\n";
echo "====================================================\n";
if ($dryRun) {
    echo " MODO: [SIMULACIÓN / DRY-RUN] - No se realizarán cambios.\n";
} else {
    echo " MODO: [EJECUCIÓN REAL]\n";
}
echo "====================================================\n\n";

$pdo = getPDO();

// ─── 1. Escanear Referencias de Imágenes en la BD ────────────────────────────
echo "1. Escaneando referencias de imágenes en la base de datos...\n";

$imagesToProcess = [];

// A. producto_imagenes
$stmt = $pdo->query("SELECT id, url FROM producto_imagenes");
while ($row = $stmt->fetch()) {
    if (isValidUploadPath($row['url'])) {
        $imagesToProcess[] = [
            'type' => 'producto_imagenes',
            'pk_col' => 'id',
            'pk_val' => $row['id'],
            'col' => 'url',
            'url' => $row['url']
        ];
    }
}

// B. categorias
$stmt = $pdo->query("SELECT id, imagen_url FROM categorias");
while ($row = $stmt->fetch()) {
    if (isValidUploadPath($row['imagen_url'])) {
        $imagesToProcess[] = [
            'type' => 'categorias',
            'pk_col' => 'id',
            'pk_val' => $row['id'],
            'col' => 'imagen_url',
            'url' => $row['imagen_url']
        ];
    }
}

// C. campanas
$stmt = $pdo->query("SELECT id, imagen_url FROM campanas");
while ($row = $stmt->fetch()) {
    if (isValidUploadPath($row['imagen_url'])) {
        $imagesToProcess[] = [
            'type' => 'campanas',
            'pk_col' => 'id',
            'pk_val' => $row['id'],
            'col' => 'imagen_url',
            'url' => $row['imagen_url']
        ];
    }
}

// D. blog_posts
$stmt = $pdo->query("SELECT id, imagen_portada FROM blog_posts");
while ($row = $stmt->fetch()) {
    if (isValidUploadPath($row['imagen_portada'])) {
        $imagesToProcess[] = [
            'type' => 'blog_posts',
            'pk_col' => 'id',
            'pk_val' => $row['id'],
            'col' => 'imagen_portada',
            'url' => $row['imagen_portada']
        ];
    }
}

// E. textos
$stmt = $pdo->query("SELECT clave, valor FROM textos WHERE valor LIKE '/uploads/%'");
while ($row = $stmt->fetch()) {
    if (isValidUploadPath($row['valor'])) {
        $imagesToProcess[] = [
            'type' => 'textos',
            'pk_col' => 'clave',
            'pk_val' => $row['clave'],
            'col' => 'valor',
            'url' => $row['valor']
        ];
    }
}

$totalFound = count($imagesToProcess);
echo "   -> Se encontraron {$totalFound} imágenes referenciadas en la base de datos.\n\n";

// Helper para validar ruta local
function isValidUploadPath(?string $url): bool {
    if (empty($url)) return false;
    return str_starts_with($url, '/uploads/') && !str_contains($url, '/backup_originales/');
}

// ─── 2. Procesar y Evaluar Imágenes ──────────────────────────────────────────
echo "2. Evaluando estado de archivos físicos en disco...\n";

$processedFiles = [];
$totalSizeBefore = 0;
$totalSizeAfter = 0;
$skippedCount = 0;
$optimizedCount = 0;
$errorCount = 0;

// Crear directorio de backup si es ejecución real
if (!$dryRun && !is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

foreach ($imagesToProcess as &$img) {
    $relativeUrl = $img['url'];
    $absolutePath = BASE_PATH . $relativeUrl;
    
    // Inicializar valores por defecto para evitar claves indefinidas
    $img['new_url'] = $relativeUrl;
    $img['skipped'] = true;
    
    // Normalizar slashes
    $absolutePath = str_replace('\\', '/', $absolutePath);
    
    if (!file_exists($absolutePath)) {
        echo "   [!] Archivo no encontrado: {$relativeUrl}\n";
        $errorCount++;
        continue;
    }
    
    // Si ya procesamos este archivo en este bucle, usamos los datos previos
    if (isset($processedFiles[$absolutePath])) {
        $img['new_url'] = $processedFiles[$absolutePath]['new_url'];
        $img['skipped'] = $processedFiles[$absolutePath]['skipped'];
        if ($img['skipped']) {
            $skippedCount++;
        } else {
            $optimizedCount++;
        }
        continue;
    }
    
    $sizeBefore = filesize($absolutePath);
    $totalSizeBefore += $sizeBefore;
    
    $pathInfo = pathinfo($absolutePath);
    $extension = strtolower($pathInfo['extension'] ?? '');
    
    $isWebP = ($extension === 'webp');
    $needsOptimization = false;
    
    // Obtener dimensiones actuales
    $dimensions = @getimagesize($absolutePath);
    $width = $dimensions ? $dimensions[0] : 0;
    $height = $dimensions ? $dimensions[1] : 0;
    
    // Criterios de optimización:
    // A. No es WebP (debemos convertir a WebP)
    // B. Es WebP pero excede los 800px
    // C. Es WebP, no excede los 800px pero pesa más de 100KB (podemos recomprimir para optimizar)
    if (!$isWebP) {
        $needsOptimization = true;
    } elseif ($width > MAX_DIMENSION || $height > MAX_DIMENSION) {
        $needsOptimization = true;
    } elseif ($sizeBefore > 100 * 1024) {
        $needsOptimization = true;
    }
    
    if (!$needsOptimization) {
        // Omitir archivo, ya está optimizado
        $processedFiles[$absolutePath] = [
            'new_url' => $relativeUrl,
            'skipped' => true
        ];
        $img['new_url'] = $relativeUrl;
        $img['skipped'] = true;
        $totalSizeAfter += $sizeBefore;
        $skippedCount++;
        continue;
    }
    
    // Calcular ruta de salida WebP
    $newRelativeUrl = $relativeUrl;
    if (!$isWebP) {
        $newRelativeUrl = '/uploads/' . ($img['type'] === 'textos' ? 'textos/' : 'productos/') . $pathInfo['filename'] . '.webp';
    }
    $newAbsolutePath = BASE_PATH . $newRelativeUrl;
    
    // Estimación en dry-run, conversión real en ejecución real
    if ($dryRun) {
        // Estimar un ahorro promedio del 75% para JPG/PNG no optimizados o WebP sobredimensionados
        $estimatedSize = (int)($sizeBefore * 0.25);
        $totalSizeAfter += $estimatedSize;
        
        $processedFiles[$absolutePath] = [
            'new_url' => $newRelativeUrl,
            'skipped' => false
        ];
        $img['new_url'] = $newRelativeUrl;
        $img['skipped'] = false;
        $optimizedCount++;
        
        $savedKb = round(($sizeBefore - $estimatedSize) / 1024, 1);
        echo "   [SIM] Optimizaría: {$relativeUrl} ({$width}x{$height}px, " . round($sizeBefore/1024, 1) . " KB) -> " . round($estimatedSize/1024, 1) . " KB (Ahorro est: ~{$savedKb} KB)\n";
    } else {
        // EJECUCIÓN REAL:
        
        // 1. Respaldar imagen original en backup_originales
        $backupPath = BACKUP_DIR . '/' . basename($absolutePath);
        if (!file_exists($backupPath)) {
            copy($absolutePath, $backupPath);
        }
        
        // 2. Ejecutar la optimización
        $success = optimizeImageGD($absolutePath, $newAbsolutePath, MAX_DIMENSION, WEBP_QUALITY);
        
        if ($success) {
            $sizeAfter = filesize($newAbsolutePath);
            $totalSizeAfter += $sizeAfter;
            
            // 3. Si no era webp originalmente, eliminar la imagen original para no dejar basura
            if (!$isWebP && $absolutePath !== $newAbsolutePath) {
                unlink($absolutePath);
            }
            
            $processedFiles[$absolutePath] = [
                'new_url' => $newRelativeUrl,
                'skipped' => false
            ];
            $img['new_url'] = $newRelativeUrl;
            $img['skipped'] = false;
            $optimizedCount++;
            
            $savedKb = round(($sizeBefore - $sizeAfter) / 1024, 1);
            echo "   [OK] Optimizado: {$relativeUrl} (" . round($sizeBefore/1024, 1) . " KB) -> {$newRelativeUrl} (" . round($sizeAfter/1024, 1) . " KB) - Ahorro: {$savedKb} KB\n";
        } else {
            echo "   [!] Error optimizando: {$relativeUrl}\n";
            $totalSizeAfter += $sizeBefore;
            $errorCount++;
            $processedFiles[$absolutePath] = [
                'new_url' => $relativeUrl,
                'skipped' => true
            ];
            $img['new_url'] = $relativeUrl;
            $img['skipped'] = true;
        }
    }
}
unset($img);

echo "\n   -> Resumen preliminar de disco:\n";
echo "      - Imágenes omitidas (ya optimizadas): {$skippedCount}\n";
echo "      - Imágenes optimizadas/a optimizar: {$optimizedCount}\n";
echo "      - Errores/Archivos faltantes: {$errorCount}\n";
$savedTotal = $totalSizeBefore - $totalSizeAfter;
$pctSaved = $totalSizeBefore > 0 ? round(($savedTotal / $totalSizeBefore) * 100, 1) : 0;
echo "      - Peso total antes: " . round($totalSizeBefore / (1024 * 1024), 2) . " MB\n";
echo "      - Peso total después (estimado/real): " . round($totalSizeAfter / (1024 * 1024), 2) . " MB\n";
echo "      - Ahorro total: " . round($savedTotal / (1024 * 1024), 2) . " MB ({$pctSaved}% de reducción)\n\n";

// ─── 3. Actualizar la Base de Datos (Si no es Dry-Run) ────────────────────────
if ($dryRun) {
    echo "3. [SIM] Omitiendo actualizaciones en la base de datos.\n";
} else {
    echo "3. Actualizando referencias en la base de datos...\n";
    $dbUpdatesCount = 0;
    
    $pdo->beginTransaction();
    try {
        foreach ($imagesToProcess as $img) {
            if ($img['skipped'] || $img['url'] === $img['new_url']) {
                continue;
            }
            
            $sql = "UPDATE {$img['type']} SET {$img['col']} = ? WHERE {$img['pk_col']} = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$img['new_url'], $img['pk_val']]);
            $dbUpdatesCount++;
        }
        $pdo->commit();
        echo "   -> Se actualizaron {$dbUpdatesCount} referencias en las tablas de la BD con éxito.\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "   [FATAL] Error actualizando la base de datos: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\n====================================================\n";
echo "      ¡PROCESO FINALIZADO CON ÉXITO!\n";
echo "====================================================\n\n";


// ─── FUNCIÓN AUXILIAR: OPTIMIZACIÓN Y REDIMENSIÓN CON GD ─────────────────────
function optimizeImageGD(string $filePath, string $targetPath, int $maxDim, int $quality): bool {
    $info = @getimagesize($filePath);
    if (!$info) return false;
    
    $w = $info[0];
    $h = $info[1];
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($filePath);
            break;
        case 'image/webp':
            $src = @imagecreatefromwebp($filePath);
            break;
        case 'image/bmp':
        case 'image/x-ms-bmp':
            $src = @imagecreatefrombmp($filePath);
            break;
        default:
            return false;
    }
    
    if (!$src) return false;
    
    // Calcular nuevas dimensiones manteniendo la relación de aspecto
    $newW = $w;
    $newH = $h;
    if ($w > $maxDim || $h > $maxDim) {
        if ($w > $h) {
            $newW = $maxDim;
            $newH = (int)round($h * ($maxDim / $w));
        } else {
            $newH = $maxDim;
            $newW = (int)round($w * ($maxDim / $h));
        }
    }
    
    $dst = imagecreatetruecolor($newW, $newH);
    if (!$dst) {
        imagedestroy($src);
        return false;
    }
    
    // Preservar transparencia para PNG y WebP
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    
    // Guardar temporalmente a WebP
    $tempFile = $targetPath . '.tmp';
    $result = imagewebp($dst, $tempFile, $quality);
    
    imagedestroy($src);
    imagedestroy($dst);
    
    if ($result && file_exists($tempFile)) {
        // Reemplazar de forma atómica
        rename($tempFile, $targetPath);
        return true;
    }
    
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    return false;
}
