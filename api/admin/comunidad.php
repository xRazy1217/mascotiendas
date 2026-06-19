<?php
// Controlador de Comunidad para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
}

function comunidadList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM comunidad_contenido ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function comunidadSave(PDO $pdo): void {
    $id          = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $titulo      = sanitize($_POST['titulo'] ?? '');
    $descripcion = $_POST['descripcion'] ?? '';
    $imagen_url  = sanitize($_POST['imagen_url'] ?? '');
    $enlace_url  = sanitize($_POST['enlace_url'] ?? '');
    $cta_texto   = sanitize($_POST['cta_texto'] ?? 'Participar ahora');
    $fecha_ev    = !empty($_POST['fecha_evento']) ? sanitize($_POST['fecha_evento']) : null;
    $activo      = !empty($_POST['activo']) ? 1 : 0;

    if (!$titulo || !$descripcion) {
        jsonResponse(['error' => 'El título y la descripción son obligatorios'], 400);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE comunidad_contenido SET titulo = ?, descripcion = ?, imagen_url = ?, enlace_url = ?, cta_texto = ?, fecha_evento = ?, activo = ? WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $imagen_url, $enlace_url, $cta_texto, $fecha_ev, $activo, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO comunidad_contenido (titulo, descripcion, imagen_url, enlace_url, cta_texto, fecha_evento, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $imagen_url, $enlace_url, $cta_texto, $fecha_ev, $activo]);
        $id = (int)$pdo->lastInsertId();
    }

    jsonResponse(['ok' => true, 'id' => $id]);
}

function comunidadDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID no válido'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM comunidad_contenido WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['ok' => true]);
}

function comunidadImagenUpload(PDO $pdo): void {
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

    $dir = __DIR__ . '/../../uploads/comunidad/';
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $name = pathinfo($file['name'], PATHINFO_FILENAME);
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9_-]/', '', $name);
    $filename = 'com_' . $name . '_' . time() . '.webp';
    $dest = $dir . $filename;

    // Cargar imagen con GD
    switch($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $img = imagecreatefromjpeg($tmp);
            break;
        case 'image/png':
            $img = imagecreatefrompng($tmp);
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

    // Redimensionar si es muy grande (max 1200px)
    $w = imagesx($img);
    $h = imagesy($img);
    $max = 1200;
    if ($w > $max || $h > $max) {
        if ($w > $h) {
            $newW = $max;
            $newH = (int)round($h * ($max / $w));
        } else {
            $newH = $max;
            $newW = (int)round($w * ($max / $h));
        }
        $newImg = imagecreatetruecolor($newW, $newH);
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
        'url' => '/uploads/comunidad/' . $filename
    ]);
}
