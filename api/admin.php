<?php
require_once __DIR__ . '/../includes/funciones.php';

session_name('mascotiendas');
ini_set('session.cookie_path', '/');
session_start();
if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    jsonResponse(['error' => 'Sin permisos'], 403);
}

$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    // Productos
    'productos_list'  => adminProductos($pdo),
    'producto_save'   => guardarProducto($pdo),
    'producto_delete' => eliminarProducto($pdo),
    'imagen_upload'   => subirImagen($pdo),
    'imagen_delete'   => eliminarImagen($pdo),
    // Pedidos
    'pedidos_list'    => adminPedidos($pdo),
    'pedido_estado'   => cambiarEstado($pdo),
    'pedido_detalle'  => detallePedido($pdo),
    // Usuarios
    'usuarios_list'   => adminUsuarios($pdo),
    // Dashboard
    'stats'           => stats($pdo),
    'bajo_stock'      => bajoStock($pdo),
    // Notificaciones
    'notif_list'      => notifList($pdo),
    'notif_leer'      => notifLeer($pdo),
    'notif_leer_todas'=> notifLeerTodas($pdo),
    // Cupones
    'cupones_list'    => cuponesList($pdo),
    'cupon_save'      => cuponSave($pdo),
    'cupon_delete'    => cuponDelete($pdo),
    'cupon_validar'   => cuponValidar($pdo),
    // Marketing
    'newsletter_list' => newsletterList($pdo),
    // Blog
    'blog_list'       => blogList($pdo),
    'blog_save'       => blogSave($pdo),
    'blog_delete'     => blogDelete($pdo),
    // Variantes
    'variantes_list'  => variantesList($pdo),
    'variante_save'   => varianteSave($pdo),
    'variante_delete' => varianteDelete($pdo),
    'atributos_list'  => atributosList($pdo),
    // Reviews
    'reviews_list'    => reviewsList($pdo),
    'review_aprobar'  => reviewAprobar($pdo),
    'review_delete'   => reviewDelete($pdo),
    // Analytics
    'analytics'       => analyticsData($pdo),
    'carritos_abandonados' => carritosAbandonados($pdo),
    // Configuraciones
    'config_get'      => configGet($pdo),
    'config_save'     => configSave($pdo),
    // Textos
    'textos_get'      => textosGet($pdo),
    'texto_save'      => textoSave($pdo),
    // Campanas
    'campanas_list'   => campanasList($pdo),
    'campana_save'    => campanaSave($pdo),
    'campana_delete'  => campanaDelete($pdo),
    'campana_toggle'  => campanaToggle($pdo),
    default           => jsonResponse(['error' => 'Accion no valida'], 400)
};

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

function subirImagen(PDO $pdo): void {
    $productoId = (int)($_POST['producto_id'] ?? 0);
    if (!$productoId || empty($_FILES['imagen'])) jsonResponse(['error' => 'Datos incompletos'], 400);

    $file    = $_FILES['imagen'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) jsonResponse(['error' => 'Formato no permitido'], 400);
    if ($file['size'] > 5 * 1024 * 1024) jsonResponse(['error' => 'Max 5MB'], 400);

    $pos = $pdo->prepare("SELECT COALESCE(MAX(posicion),-1)+1 FROM producto_imagenes WHERE producto_id=?");
    $pos->execute([$productoId]);
    $posicion = (int)$pos->fetchColumn();

    $filename = "producto_{$productoId}_{$posicion}." . $ext;
    $destDir  = __DIR__ . '/../uploads/productos/';
    if (!move_uploaded_file($file['tmp_name'], $destDir . $filename)) jsonResponse(['error' => 'Error al guardar'], 500);

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
        $path = __DIR__ . '/..' . $img['url'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare("DELETE FROM producto_imagenes WHERE id=?")->execute([$id]);
    }
    jsonResponse(['ok' => true]);
}

// ─── PEDIDOS ─────────────────────────────────────────────
function adminPedidos(PDO $pdo): void {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;
    $estado = $_GET['estado'] ?? '';
    $q      = $_GET['q'] ?? '';

    $where  = ['1=1'];
    $params = [];
    if ($estado) { $where[] = 'p.estado=?'; $params[] = $estado; }
    if ($q)      { $where[] = '(p.nombre_cliente LIKE ? OR p.email_cliente LIKE ? OR p.id=?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = (int)$q; }

    $whereSQL = implode(' AND ', $where);

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos p WHERE $whereSQL");
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre as nombre_usuario,
               (SELECT COUNT(*) FROM pedido_items WHERE pedido_id=p.id) as total_items
        FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id=u.id
        WHERE $whereSQL ORDER BY p.creado_en DESC LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);

    jsonResponse(['pedidos' => $stmt->fetchAll(), 'total' => $total, 'paginas' => max(1,(int)ceil($total/$limit))]);
}

function cambiarEstado(PDO $pdo): void {
    $id     = (int)($_POST['id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    $estados = ['pendiente','pagado','preparando','enviado','entregado','cancelado'];
    if (!in_array($estado, $estados)) jsonResponse(['error' => 'Estado invalido'], 400);
    $pdo->prepare("UPDATE pedidos SET estado=? WHERE id=?")->execute([$estado,$id]);
    jsonResponse(['ok' => true]);
}

function detallePedido(PDO $pdo): void {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT p.*, u.nombre as nombre_usuario, u.email as email_usuario FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id=u.id WHERE p.id=?");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();
    if (!$pedido) jsonResponse(['error' => 'No encontrado'], 404);
    $items = $pdo->prepare("SELECT * FROM pedido_items WHERE pedido_id=?");
    $items->execute([$id]);
    $pedido['items'] = $items->fetchAll();
    jsonResponse($pedido);
}

// ─── USUARIOS ────────────────────────────────────────────
function adminUsuarios(PDO $pdo): void {
    $q = $_GET['q'] ?? '';
    $where  = $q ? "WHERE nombre LIKE ? OR email LIKE ?" : "";
    $params = $q ? ["%$q%","%$q%"] : [];
    $stmt = $pdo->prepare("SELECT u.id,u.nombre,u.apellido,u.email,u.rol,u.activo,u.creado_en,
        (SELECT COUNT(*) FROM pedidos WHERE usuario_id=u.id) as total_pedidos,
        (SELECT COALESCE(SUM(total),0) FROM pedidos WHERE usuario_id=u.id AND estado!='cancelado') as total_gastado
        FROM usuarios u $where ORDER BY u.creado_en DESC");
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

// ─── DASHBOARD ───────────────────────────────────────────
function stats(PDO $pdo): void {
    $ventas      = $pdo->query("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as pedidos FROM pedidos WHERE estado!='cancelado'")->fetch();
    $hoy         = $pdo->query("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as pedidos FROM pedidos WHERE DATE(creado_en)=CURDATE() AND estado!='cancelado'")->fetch();
    $usuarios    = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='cliente'")->fetchColumn();
    $productos   = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();
    $pendientes  = (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado='pendiente'")->fetchColumn();
    $sin_stock   = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE en_stock=0 AND activo=1")->fetchColumn();
    $bajo_stock  = (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE en_stock=1 AND inventario_actual<=stock_minimo AND inventario_actual>0 AND activo=1")->fetchColumn();
    $no_leidas   = (int)$pdo->query("SELECT COUNT(*) FROM notificaciones WHERE leida=0")->fetchColumn();

    // Ventas ultimos 7 dias
    $ventas7 = $pdo->query("
        SELECT DATE(creado_en) as fecha, COALESCE(SUM(total),0) as total, COUNT(*) as pedidos
        FROM pedidos WHERE creado_en >= DATE_SUB(NOW(),INTERVAL 7 DAY) AND estado!='cancelado'
        GROUP BY DATE(creado_en) ORDER BY fecha ASC
    ")->fetchAll();

    // Top productos
    $top = $pdo->query("
        SELECT pi.nombre, SUM(pi.cantidad) as vendidos, SUM(pi.precio*pi.cantidad) as ingresos
        FROM pedido_items pi JOIN pedidos p ON pi.pedido_id=p.id
        WHERE p.estado!='cancelado'
        GROUP BY pi.producto_id, pi.nombre ORDER BY vendidos DESC LIMIT 5
    ")->fetchAll();

    jsonResponse(compact('ventas','hoy','usuarios','productos','pendientes','sin_stock','bajo_stock','no_leidas','ventas7','top'));
}

function bajoStock(PDO $pdo): void {
    $stmt = $pdo->query("
        SELECT id, nombre, inventario_actual, stock_minimo, en_stock,
               (SELECT url FROM producto_imagenes WHERE producto_id=productos.id AND posicion=0 LIMIT 1) as imagen
        FROM productos WHERE (en_stock=0 OR inventario_actual<=stock_minimo) AND activo=1
        ORDER BY en_stock ASC, inventario_actual ASC LIMIT 20
    ");
    jsonResponse($stmt->fetchAll());
}

// ─── NOTIFICACIONES ──────────────────────────────────────
function notifList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM notificaciones ORDER BY creado_en DESC LIMIT 30");
    jsonResponse($stmt->fetchAll());
}

function notifLeer(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE notificaciones SET leida=1 WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function notifLeerTodas(PDO $pdo): void {
    $pdo->query("UPDATE notificaciones SET leida=1");
    jsonResponse(['ok' => true]);
}

// ─── CUPONES ─────────────────────────────────────────────
function cuponesList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM cupones ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function cuponSave(PDO $pdo): void {
    $id       = (int)($_POST['id'] ?? 0);
    $codigo   = strtoupper(sanitize($_POST['codigo'] ?? ''));
    $tipo     = $_POST['tipo'] ?? 'porcentaje';
    $valor    = (int)($_POST['valor'] ?? 0);
    $minimo   = (int)($_POST['minimo_compra'] ?? 0);
    $usos_max = !empty($_POST['usos_max']) ? (int)$_POST['usos_max'] : null;
    $activo   = (int)($_POST['activo'] ?? 1);
    $expira   = !empty($_POST['expira_en']) ? $_POST['expira_en'] : null;

    if (!$codigo || !$valor) jsonResponse(['error' => 'Codigo y valor requeridos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE cupones SET codigo=?,tipo=?,valor=?,minimo_compra=?,usos_max=?,activo=?,expira_en=? WHERE id=?")
            ->execute([$codigo,$tipo,$valor,$minimo,$usos_max,$activo,$expira,$id]);
    } else {
        $pdo->prepare("INSERT INTO cupones (codigo,tipo,valor,minimo_compra,usos_max,activo,expira_en) VALUES (?,?,?,?,?,?,?)")
            ->execute([$codigo,$tipo,$valor,$minimo,$usos_max,$activo,$expira]);
    }
    jsonResponse(['ok' => true]);
}

function cuponDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM cupones WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function cuponValidar(PDO $pdo): void {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $total  = (int)($_POST['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM cupones WHERE codigo=? AND activo=1");
    $stmt->execute([$codigo]);
    $cupon = $stmt->fetch();

    if (!$cupon) jsonResponse(['error' => 'Cupon no valido'], 404);
    if ($cupon['expira_en'] && $cupon['expira_en'] < date('Y-m-d')) jsonResponse(['error' => 'Cupon expirado'], 400);
    if ($cupon['usos_max'] && $cupon['usos_actual'] >= $cupon['usos_max']) jsonResponse(['error' => 'Cupon agotado'], 400);
    if ($total < $cupon['minimo_compra']) jsonResponse(['error' => 'Minimo de compra: $'.number_format($cupon['minimo_compra'],0,',','.')], 400);

    $descuento = $cupon['tipo'] === 'porcentaje'
        ? (int)round($total * $cupon['valor'] / 100)
        : min($cupon['valor'], $total);

    jsonResponse(['ok' => true, 'cupon' => $cupon, 'descuento' => $descuento]);
}

// ─── MARKETING ─────────────────────────────────────────
function newsletterList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM newsletter WHERE activo=1 ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

// ─── BLOG ────────────────────────────────────────────────
function blogList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT id,titulo,slug,imagen_portada,publicado,creado_en FROM blog_posts ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function blogSave(PDO $pdo): void {
    $id       = (int)($_POST['id'] ?? 0);
    $titulo   = sanitize($_POST['titulo'] ?? '');
    $slug     = sanitize($_POST['slug'] ?? '');
    $extracto = $_POST['extracto'] ?? '';
    $contenido= $_POST['contenido'] ?? '';
    $imagen   = sanitize($_POST['imagen_portada'] ?? '');
    $meta_t   = sanitize($_POST['meta_titulo'] ?? '');
    $meta_d   = $_POST['meta_descripcion'] ?? '';
    $publicado= (int)($_POST['publicado'] ?? 0);
    $autor_id = $_SESSION['usuario_id'];

    if (!$titulo || !$slug) jsonResponse(['error' => 'Titulo y slug requeridos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE blog_posts SET titulo=?,slug=?,extracto=?,contenido=?,imagen_portada=?,meta_titulo=?,meta_descripcion=?,publicado=? WHERE id=?")
            ->execute([$titulo,$slug,$extracto,$contenido,$imagen,$meta_t,$meta_d,$publicado,$id]);
    } else {
        $pdo->prepare("INSERT INTO blog_posts (titulo,slug,extracto,contenido,imagen_portada,meta_titulo,meta_descripcion,publicado,autor_id) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$titulo,$slug,$extracto,$contenido,$imagen,$meta_t,$meta_d,$publicado,$autor_id]);
        $id = (int)$pdo->lastInsertId();
    }
    jsonResponse(['ok' => true, 'id' => $id]);
}

function blogDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([$id]);
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

// ─── REVIEWS ─────────────────────────────────────────────
function reviewsList(PDO $pdo): void {
    $aprobado = isset($_GET['aprobado']) ? (int)$_GET['aprobado'] : -1;
    $where = $aprobado >= 0 ? "WHERE r.aprobado=$aprobado" : "";
    $stmt = $pdo->query("SELECT r.*, p.nombre as producto_nombre FROM reviews r JOIN productos p ON r.producto_id=p.id $where ORDER BY r.creado_en DESC LIMIT 50");
    jsonResponse($stmt->fetchAll());
}

function reviewAprobar(PDO $pdo): void {
    $id  = (int)($_POST['id'] ?? 0);
    $val = (int)($_POST['aprobado'] ?? 1);
    $pdo->prepare("UPDATE reviews SET aprobado=? WHERE id=?")->execute([$val, $id]);
    jsonResponse(['ok' => true]);
}

function reviewDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

// ─── ANALYTICS ───────────────────────────────────────────
function analyticsData(PDO $pdo): void {
    $visitas_hoy  = (int)$pdo->query("SELECT COUNT(*) FROM analytics WHERE DATE(creado_en)=CURDATE()")->fetchColumn();
    $visitas_mes  = (int)$pdo->query("SELECT COUNT(*) FROM analytics WHERE MONTH(creado_en)=MONTH(NOW()) AND YEAR(creado_en)=YEAR(NOW())")->fetchColumn();
    $top_productos = $pdo->query("SELECT referencia as producto_id, COUNT(*) as vistas FROM analytics WHERE tipo='vista_producto' GROUP BY referencia ORDER BY vistas DESC LIMIT 5")->fetchAll();
    $checkouts    = (int)$pdo->query("SELECT COUNT(*) FROM analytics WHERE tipo='checkout'")->fetchColumn();
    $carritos     = (int)$pdo->query("SELECT COUNT(*) FROM analytics WHERE tipo='carrito'")->fetchColumn();
    jsonResponse(compact('visitas_hoy','visitas_mes','top_productos','checkouts','carritos'));
}

function carritosAbandonados(PDO $pdo): void {
    $stmt = $pdo->query("SELECT ca.*, u.nombre, u.email as user_email FROM carritos_abandonados ca LEFT JOIN usuarios u ON ca.usuario_id=u.id WHERE ca.recuperado=0 ORDER BY ca.actualizado_en DESC LIMIT 30");
    jsonResponse($stmt->fetchAll());
}

// ─── CONFIGURACIONES ─────────────────────────────────────
function configGet(PDO $pdo): void {
    $stmt = $pdo->query("SELECT clave, valor FROM configuraciones");
    $rows = $stmt->fetchAll();
    $config = [];
    foreach ($rows as $r) $config[$r['clave']] = $r['valor'];
    jsonResponse($config);
}

function configSave(PDO $pdo): void {
    $clave = sanitize($_POST['clave'] ?? '');
    $valor = sanitize($_POST['valor'] ?? '');
    if (!$clave) jsonResponse(['error' => 'Clave requerida'], 400);
    $pdo->prepare("INSERT INTO configuraciones (clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?")
        ->execute([$clave, $valor, $valor]);
    jsonResponse(['ok' => true]);
}

// ─── TEXTOS ─────────────────────────────────────────
function textosGet(PDO $pdo): void {
    $stmt = $pdo->query("SELECT clave, valor FROM textos");
    $rows = $stmt->fetchAll();
    $textos = [];
    foreach ($rows as $r) $textos[$r['clave']] = $r['valor'];
    jsonResponse($textos);
}

function textoSave(PDO $pdo): void {
    $clave = sanitize($_POST['clave'] ?? '');
    $valor = trim($_POST['valor'] ?? '');
    if (!$clave) jsonResponse(['error' => 'Clave requerida'], 400);
    $pdo->prepare("INSERT INTO textos (clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?")
        ->execute([$clave, $valor, $valor]);
    jsonResponse(['ok' => true]);
}

// ─── CAMPANAS ────────────────────────────────────────
function campanasList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM campanas ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function campanaSave(PDO $pdo): void {
    $id        = (int)($_POST['id'] ?? 0);
    $nombre    = sanitize($_POST['nombre'] ?? '');
    $titulo    = sanitize($_POST['titulo'] ?? '');
    $texto     = trim($_POST['texto'] ?? '');
    $imagen    = sanitize($_POST['imagen_url'] ?? '');
    $btn_texto = sanitize($_POST['btn_texto'] ?? '');
    $btn_url   = sanitize($_POST['btn_url'] ?? '');
    $activacion= $_POST['activacion'] ?? 'exit';
    $segundos  = (int)($_POST['segundos'] ?? 5);
    $fecha_ini = !empty($_POST['fecha_ini']) ? $_POST['fecha_ini'] : null;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $activo    = (int)($_POST['activo'] ?? 1);

    if (!$nombre || !$titulo) jsonResponse(['error' => 'Nombre y título requeridos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE campanas SET nombre=?,titulo=?,texto=?,imagen_url=?,btn_texto=?,btn_url=?,activacion=?,segundos=?,fecha_ini=?,fecha_fin=?,activo=? WHERE id=?")
            ->execute([$nombre,$titulo,$texto,$imagen,$btn_texto,$btn_url,$activacion,$segundos,$fecha_ini,$fecha_fin,$activo,$id]);
    } else {
        $pdo->prepare("INSERT INTO campanas (nombre,titulo,texto,imagen_url,btn_texto,btn_url,activacion,segundos,fecha_ini,fecha_fin,activo) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$nombre,$titulo,$texto,$imagen,$btn_texto,$btn_url,$activacion,$segundos,$fecha_ini,$fecha_fin,$activo]);
    }
    jsonResponse(['ok' => true]);
}

function campanaDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM campanas WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function campanaToggle(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE campanas SET activo = 1 - activo WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}
