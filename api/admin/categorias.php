<?php
if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

function adminCategoriasList(PDO $pdo): void {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(pc.producto_id) as total_productos 
        FROM categorias c 
        LEFT JOIN producto_categorias pc ON c.id = pc.categoria_id 
        GROUP BY c.id 
        ORDER BY c.nombre ASC
    ");
    jsonResponse($stmt->fetchAll());
}

function guardarCategoria(PDO $pdo): void {
    $id             = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nombre         = sanitize($_POST['nombre'] ?? '');
    $slug           = sanitize($_POST['slug'] ?? '');
    $meta_titulo    = sanitize($_POST['meta_titulo'] ?? '');
    $meta_desc     = sanitize($_POST['meta_descripcion'] ?? '');
    $imagen_url     = sanitize($_POST['imagen_url'] ?? '');
    $mostrar_home   = !empty($_POST['mostrar_home']) ? 1 : 0;

    if (!$nombre) {
        jsonResponse(['error' => 'El nombre es obligatorio'], 400);
    }

    if (!$slug) {
        // Generar slug automático si no viene indicado
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[áàäâ]/u', 'a', $slug);
        $slug = preg_replace('/[éèëê]/u', 'e', $slug);
        $slug = preg_replace('/[íìïî]/u', 'i', $slug);
        $slug = preg_replace('/[óòöô]/u', 'o', $slug);
        $slug = preg_replace('/[úùüû]/u', 'u', $slug);
        $slug = preg_replace('/ñ/u', 'n', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
    }

    // Verificar unicidad de slug
    $check = $pdo->prepare("SELECT id FROM categorias WHERE slug = ? AND id != ?");
    $check->execute([$slug, $id ?: 0]);
    if ($check->fetch()) {
        jsonResponse(['error' => 'Ya existe una categoría con este slug/enlace: ' . $slug], 400);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, slug = ?, meta_titulo = ?, meta_descripcion = ?, imagen_url = ?, mostrar_home = ? WHERE id = ?");
        $stmt->execute([$nombre, $slug, $meta_titulo, $meta_desc, $imagen_url, $mostrar_home, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, slug, meta_titulo, meta_descripcion, imagen_url, mostrar_home) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $slug, $meta_titulo, $meta_desc, $imagen_url, $mostrar_home]);
        $id = (int)$pdo->lastInsertId();
    }

    jsonResponse(['ok' => true, 'id' => $id]);
}

function eliminarCategoria(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID no válido'], 400);
    }

    // Evitar eliminar la categoría de farmacia por defecto para no romper filtros duros
    $check = $pdo->prepare("SELECT slug FROM categorias WHERE id = ?");
    $check->execute([$id]);
    $cat = $check->fetch();
    if ($cat && $cat['slug'] === 'farmacia-mascotas') {
        jsonResponse(['error' => 'La categoría del sistema para Farmacia no puede ser eliminada para garantizar el enrutamiento correcto.'], 400);
    }

    $pdo->beginTransaction();
    try {
        // Eliminar relaciones con productos
        $pdo->prepare("DELETE FROM producto_categorias WHERE categoria_id = ?")->execute([$id]);
        // Eliminar categoría
        $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
        $pdo->commit();
        jsonResponse(['ok' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Error al eliminar la categoría'], 500);
    }
}

function subirImagenCategoria(PDO $pdo): void {
    if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Archivo no recibido o con errores'], 400);
    }

    $file = $_FILES['imagen'];
    $tmp  = $file['tmp_name'];

    // Validar tipo de archivo (MIME)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed)) {
        jsonResponse(['error' => 'Tipo de archivo no permitido (MIME-type inválido)'], 400);
    }

    $dir = __DIR__ . '/../../uploads/categorias/';
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $name = pathinfo($file['name'], PATHINFO_FILENAME);
    // Limpiar nombre
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9_-]/', '', $name);
    $filename = 'cat_' . $name . '_' . time() . '.webp';
    $dest = $dir . $filename;

    // Cargar imagen con GD
    switch($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $img = imagecreatefromjpeg($tmp);
            break;
        case 'image/png':
            $img = imagecreatefrompng($tmp);
            // Preservar transparencia
            imagealphablending($img, true);
            imagesavealpha($img, true);
            break;
        case 'image/webp':
            $img = imagecreatefromwebp($tmp);
            break;
        default:
            jsonResponse(['error' => 'Formato no soportado'], 400);
            return;
    }

    if (!$img) {
        jsonResponse(['error' => 'No se pudo cargar la imagen'], 400);
    }

    // Redimensionar si es muy grande (ej: max 800px)
    $w = imagesx($img);
    $h = imagesy($img);
    $max = 800;
    if ($w > $max || $h > $max) {
        if ($w > $h) {
            $newW = $max;
            $newH = (int)round($h * ($max / $w));
        } else {
            $newH = $max;
            $newW = (int)round($w * ($max / $h));
        }
        $newImg = imagecreatetruecolor($newW, $newH);
        // Preservar transparencia en WebP
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);
        $img = $newImg;
    }

    // Guardar como WebP
    if (!imagewebp($img, $dest, 80)) {
        imagedestroy($img);
        jsonResponse(['error' => 'Error al procesar la imagen WebP'], 500);
    }

    imagedestroy($img);
    jsonResponse([
        'ok' => true,
        'url' => '/uploads/categorias/' . $filename
    ]);
}
