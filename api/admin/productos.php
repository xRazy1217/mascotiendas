<?php
// Controlador de Productos y Variantes para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
}

// ─── PRODUCTOS ───────────────────────────────────────────
function adminProductos(PDO $pdo): void {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = (int)($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;
    $q      = $_GET['q'] ?? '';
    $stock  = $_GET['stock'] ?? ''; // 'bajo' | 'sin' | ''

    $where  = ['1=1'];
    $params = [];

    if ($q) { $where[] = 'nombre LIKE ?'; $params[] = "%$q%"; }
    if ($stock === 'sin')  { $where[] = 'en_stock = 0'; }
    if ($stock === 'bajo') { $where[] = 'en_stock = 1 AND inventario_actual <= stock_minimo AND inventario_actual > 0'; }

    $ordenMap = [
        'id_desc'     => 'p.id DESC',
        'id_asc'      => 'p.id ASC',
        'precio_asc'  => 'p.precio_normal ASC',
        'precio_desc' => 'p.precio_normal DESC',
        'nombre_asc'  => 'p.nombre ASC',
        'nombre_desc' => 'p.nombre DESC',
        'stock_asc'   => 'p.inventario_actual ASC',
        'stock_desc'  => 'p.inventario_actual DESC',
    ];
    $orden = $_GET['orden'] ?? 'id_desc';
    $orderSQL = $ordenMap[$orden] ?? 'p.id DESC';

    $whereSQL = implode(' AND ', $where);

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE $whereSQL");
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.id, p.nombre, p.precio_normal, p.precio_rebajado, p.en_stock,
               p.activo, p.sku, p.inventario_actual, p.stock_minimo,
               (SELECT url FROM producto_imagenes WHERE producto_id = p.id AND posicion = 0 LIMIT 1) as imagen
        FROM productos p WHERE $whereSQL ORDER BY $orderSQL LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);

    jsonResponse([
        'productos' => $stmt->fetchAll(),
        'total'     => $total,
        'paginas'   => max(1, (int)ceil($total / $limit))
    ]);
}

function guardarProducto(PDO $pdo): void {
    $id         = (int)($_POST['id'] ?? 0);
    $nombre     = sanitize($_POST['nombre'] ?? '');
    $desc_corta = $_POST['descripcion_corta'] ?? '';
    $desc       = $_POST['descripcion'] ?? '';
    $precio     = (int)($_POST['precio_normal'] ?? 0);
    $rebajado   = !empty($_POST['precio_rebajado']) ? (int)$_POST['precio_rebajado'] : null;
    $en_stock   = (int)($_POST['en_stock'] ?? 1);
    $activo     = (int)($_POST['activo'] ?? 1);
    $sku        = sanitize($_POST['sku'] ?? '');
    $inventario = (int)($_POST['inventario_actual'] ?? 0);
    $stock_min  = (int)($_POST['stock_minimo'] ?? 5);
    $categorias = json_decode($_POST['categorias'] ?? '[]', true);

    if (!$nombre || !$precio) jsonResponse(['error' => 'Nombre y precio requeridos'], 400);

    // Leer stock anterior ANTES de actualizar
    $stockAnterior = 1;
    if ($id) {
        $prev = $pdo->prepare("SELECT en_stock FROM productos WHERE id = ?");
        $prev->execute([$id]);
        $stockAnterior = (int)($prev->fetchColumn() ?? 1);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE productos SET nombre=?,descripcion_corta=?,descripcion=?,precio_normal=?,precio_rebajado=?,en_stock=?,activo=?,sku=?,inventario_actual=?,stock_minimo=? WHERE id=?");
        $stmt->execute([$nombre,$desc_corta,$desc,$precio,$rebajado,$en_stock,$activo,$sku,$inventario,$stock_min,$id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre,descripcion_corta,descripcion,precio_normal,precio_rebajado,en_stock,activo,sku,inventario_actual,stock_minimo) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nombre,$desc_corta,$desc,$precio,$rebajado,$en_stock,$activo,$sku,$inventario,$stock_min]);
        $id = (int)$pdo->lastInsertId();
    }

    $pdo->prepare("DELETE FROM producto_categorias WHERE producto_id=?")->execute([$id]);
    if (!empty($categorias)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO producto_categorias (producto_id,categoria_id) VALUES (?,?)");
        foreach ($categorias as $catId) $ins->execute([$id,(int)$catId]);
    }

    // Notificar solo si el stock CAMBIO de con stock a sin stock
    if (!$en_stock && $stockAnterior === 1) {
        $pdo->prepare("INSERT INTO notificaciones (tipo,titulo,mensaje,url) VALUES ('stock','Producto sin stock',?,?)")
            ->execute(["El producto '$nombre' fue marcado sin stock.", "/admin/?s=productos"]);
    }

    jsonResponse(['ok' => true, 'id' => $id]);
}

function eliminarProducto(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM productos WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function recreateUploadedImage(string $tmpPath, string $destPath, string $mime): bool {
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $img = @imagecreatefromjpeg($tmpPath);
            if ($img) {
                $ok = imagejpeg($img, $destPath, 85);
                imagedestroy($img);
                return $ok;
            }
            break;
        case 'image/png':
            $img = @imagecreatefrompng($tmpPath);
            if ($img) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                $ok = imagepng($img, $destPath, 8);
                imagedestroy($img);
                return $ok;
            }
            break;
        case 'image/webp':
            $img = @imagecreatefromwebp($tmpPath);
            if ($img) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                $ok = imagewebp($img, $destPath, 80);
                imagedestroy($img);
                return $ok;
            }
            break;
        case 'image/gif':
            $img = @imagecreatefromgif($tmpPath);
            if ($img) {
                $ok = imagegif($img, $destPath);
                imagedestroy($img);
                return $ok;
            }
            break;
    }
    return false;
}

function subirImagen(PDO $pdo): void {
    $productoId = (int)($_POST['producto_id'] ?? 0);
    if (!$productoId || empty($_FILES['imagen'])) jsonResponse(['error' => 'Datos incompletos'], 400);

    $file    = $_FILES['imagen'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) jsonResponse(['error' => 'Formato no permitido por extensión'], 400);
    if ($file['size'] > 5 * 1024 * 1024) jsonResponse(['error' => 'Max 5MB'], 400);

    // Hardening: Validar tipo MIME real usando finfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowedMimes)) {
            jsonResponse(['error' => 'Tipo de archivo no permitido (MIME-type inválido)'], 400);
        }
    } else {
        $mime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
    }

    $pos = $pdo->prepare("SELECT COALESCE(MAX(posicion),-1)+1 FROM producto_imagenes WHERE producto_id=?");
    $pos->execute([$productoId]);
    $posicion = (int)$pos->fetchColumn();

    $filename = "producto_{$productoId}_{$posicion}." . $ext;
    $destDir  = __DIR__ . '/../../uploads/productos/';
    $destPath = $destDir . $filename;

    // Hardening: Regenerar la imagen usando la librería GD para limpiar metadatos payload (EXIF/malware)
    $gdEnabled = extension_loaded('gd') && function_exists('imagecreatefromjpeg');
    if ($gdEnabled) {
        $ok = recreateUploadedImage($file['tmp_name'], $destPath, $mime);
    } else {
        $ok = move_uploaded_file($file['tmp_name'], $destPath);
    }

    if (!$ok) jsonResponse(['error' => 'Error al procesar o guardar la imagen'], 500);

    $url = '/uploads/productos/' . $filename;
    $pdo->prepare("INSERT INTO producto_imagenes (producto_id,url,posicion) VALUES (?,?,?)")->execute([$productoId,$url,$posicion]);
    jsonResponse(['ok' => true, 'url' => $url, 'posicion' => $posicion, 'id' => (int)$pdo->lastInsertId()]);
}

function eliminarImagen(PDO $pdo): void {
    $id   = (int)($_POST['imagen_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT url FROM producto_imagenes WHERE id=?");
    $stmt->execute([$id]);
    $img  = $stmt->fetch();
    if ($img) {
        $path = __DIR__ . '/../..' . $img['url'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare("DELETE FROM producto_imagenes WHERE id=?")->execute([$id]);
    }
    jsonResponse(['ok' => true]);
}

function guardarRecorteImagen(PDO $pdo): void {
    $id = (int)($_POST['imagen_id'] ?? 0);
    $cropConfig = $_POST['crop_config'] ?? null;
    if (!$id) jsonResponse(['error' => 'ID de imagen requerido'], 400);
    
    if ($cropConfig !== null) {
        json_decode($cropConfig);
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonResponse(['error' => 'Configuración de recorte inválida (debe ser JSON)'], 400);
        }
    }
    
    $stmt = $pdo->prepare("UPDATE producto_imagenes SET crop_config=? WHERE id=?");
    $stmt->execute([$cropConfig, $id]);
    
    jsonResponse(['ok' => true]);
}

// ─── VARIANTES ───────────────────────────────────────────
function atributosList(PDO $pdo): void {
    $stmt = $pdo->query("
        SELECT a.id, a.nombre,
               GROUP_CONCAT(JSON_OBJECT('id',av.id,'valor',av.valor) ORDER BY av.valor) as valores_raw
        FROM atributos a LEFT JOIN atributo_valores av ON av.atributo_id=a.id
        GROUP BY a.id ORDER BY a.nombre
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['valores'] = $r['valores_raw'] ? json_decode('[' . $r['valores_raw'] . ']') : [];
        unset($r['valores_raw']);
    }
    jsonResponse($rows);
}

function variantesList(PDO $pdo): void {
    $pid  = (int)($_GET['producto_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT pv.*,
               GROUP_CONCAT(CONCAT(a.nombre,': ',av.valor) ORDER BY a.nombre SEPARATOR ' | ') as label
        FROM producto_variantes pv
        LEFT JOIN variante_atributos va ON va.variante_id=pv.id
        LEFT JOIN atributo_valores av  ON av.id=va.atributo_valor_id
        LEFT JOIN atributos a          ON a.id=av.atributo_id
        WHERE pv.producto_id=?
        GROUP BY pv.id ORDER BY pv.id
    ");
    $stmt->execute([$pid]);
    jsonResponse($stmt->fetchAll());
}

function varianteSave(PDO $pdo): void {
    $id         = (int)($_POST['id'] ?? 0);
    $pid        = (int)($_POST['producto_id'] ?? 0);
    $sku        = sanitize($_POST['sku'] ?? '');
    $precio     = (int)($_POST['precio_normal'] ?? 0);
    $rebajado   = !empty($_POST['precio_rebajado']) ? (int)$_POST['precio_rebajado'] : null;
    $stock      = (int)($_POST['stock'] ?? 0);
    $en_stock   = (int)($_POST['en_stock'] ?? 1);
    $imagen     = sanitize($_POST['imagen_url'] ?? '');
    $atrib_vals = json_decode($_POST['atributo_valores'] ?? '[]', true);

    if (!$pid || !$precio) jsonResponse(['error' => 'Datos incompletos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE producto_variantes SET sku=?,precio_normal=?,precio_rebajado=?,stock=?,en_stock=?,imagen_url=? WHERE id=?")
            ->execute([$sku,$precio,$rebajado,$stock,$en_stock,$imagen,$id]);
    } else {
        $pdo->prepare("INSERT INTO producto_variantes (producto_id,sku,precio_normal,precio_rebajado,stock,en_stock,imagen_url) VALUES (?,?,?,?,?,?,?)")
            ->execute([$pid,$sku,$precio,$rebajado,$stock,$en_stock,$imagen]);
        $id = (int)$pdo->lastInsertId();
    }

    // Actualizar atributos
    $pdo->prepare("DELETE FROM variante_atributos WHERE variante_id=?")->execute([$id]);
    if (!empty($atrib_vals)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO variante_atributos (variante_id,atributo_valor_id) VALUES (?,?)");
        foreach ($atrib_vals as $avId) $ins->execute([$id,(int)$avId]);
    }

    // Marcar producto como con variantes
    $pdo->prepare("UPDATE productos SET tiene_variantes=1 WHERE id=?")->execute([$pid]);

    jsonResponse(['ok' => true, 'id' => $id]);
}

function varianteDelete(PDO $pdo): void {
    $id  = (int)($_POST['id'] ?? 0);
    $pid = (int)($_POST['producto_id'] ?? 0);
    $pdo->prepare("UPDATE producto_variantes SET activo=0 WHERE id=?")->execute([$id]);
    // Si no quedan variantes activas, desmarcar
    $count = $pdo->prepare("SELECT COUNT(*) FROM producto_variantes WHERE producto_id=? AND activo=1");
    $count->execute([$pid]);
    if ((int)$count->fetchColumn() === 0) {
        $pdo->prepare("UPDATE productos SET tiene_variantes=0 WHERE id=?")->execute([$pid]);
    }
    jsonResponse(['ok' => true]);
}

function productosAccionMasiva(PDO $pdo): void {
    $ids  = json_decode($_POST['ids'] ?? '[]', true);
    $tipo = $_POST['tipo_accion'] ?? '';
    $val  = isset($_POST['valor']) ? (int)$_POST['valor'] : null;

    if (empty($ids) || !is_array($ids)) {
        jsonResponse(['error' => 'No se especificaron productos'], 400);
    }
    if (!in_array($tipo, ['stock_on', 'stock_off', 'inventario_set'])) {
        jsonResponse(['error' => 'Acción no válida'], 400);
    }

    try {
        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtInfo = $pdo->prepare("SELECT id, nombre, en_stock FROM productos WHERE id IN ($placeholders)");
        $stmtInfo->execute($ids);
        $prodInfo = $stmtInfo->fetchAll(PDO::FETCH_ASSOC);

        $stmtNotif = $pdo->prepare("INSERT INTO notificaciones (tipo,titulo,mensaje,url) VALUES ('stock','Producto sin stock',?,?)");

        if ($tipo === 'stock_on') {
            $stmtUpdate = $pdo->prepare("UPDATE productos SET en_stock = 1 WHERE id IN ($placeholders)");
            $stmtUpdate->execute($ids);
        } elseif ($tipo === 'stock_off') {
            $stmtUpdate = $pdo->prepare("UPDATE productos SET en_stock = 0 WHERE id IN ($placeholders)");
            $stmtUpdate->execute($ids);

            foreach ($prodInfo as $p) {
                if ((int)$p['en_stock'] === 1) {
                    $stmtNotif->execute(["El producto '{$p['nombre']}' fue marcado sin stock.", "/admin/?s=productos"]);
                }
            }
        } elseif ($tipo === 'inventario_set') {
            if ($val === null || $val < 0) {
                jsonResponse(['error' => 'Valor de inventario inválido'], 400);
            }
            $enStock = $val > 0 ? 1 : 0;
            $stmtUpdate = $pdo->prepare("UPDATE productos SET inventario_actual = ?, en_stock = ? WHERE id IN ($placeholders)");
            $params = array_merge([$val, $enStock], $ids);
            $stmtUpdate->execute($params);

            if ($enStock === 0) {
                foreach ($prodInfo as $p) {
                    if ((int)$p['en_stock'] === 1) {
                        $stmtNotif->execute(["El producto '{$p['nombre']}' fue marcado sin stock.", "/admin/?s=productos"]);
                    }
                }
            }
        }

        $pdo->commit();
        jsonResponse(['ok' => true, 'updated_count' => count($ids)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
    }
}

