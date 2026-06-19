<?php
// Controlador de Configuración, Estadísticas y Notificaciones para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
}

// ─── DASHBOARD / STATS ───────────────────────────────────
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
    $stmt = $pdo->query("
        SELECT cs.*, u.nombre as user_nombre, u.email as user_email 
        FROM carritos_sesiones cs 
        LEFT JOIN usuarios u ON cs.id_usuario = u.id 
        WHERE cs.estado IN ('activo', 'abandonado') 
        ORDER BY cs.fecha_actualizacion DESC LIMIT 50
    ");
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
