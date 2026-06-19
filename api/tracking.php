<?php
require_once __DIR__ . '/../includes/funciones.php';
checkCSRF();

$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'track'         => track($pdo),
    'fomo'          => getFomo($pdo),
    'viendo'        => getViendo($pdo),
    'carrito_save'  => guardarCarrito($pdo),
    'carrito_get'   => obtenerCarrito($pdo),
    default         => jsonResponse(['error' => 'Accion no valida'], 400)
};

function track(PDO $pdo): void {
    $tipo = sanitize($_POST['tipo'] ?? '');
    $ref  = sanitize($_POST['referencia'] ?? '');
    $uid  = $_SESSION['usuario_id'] ?? null;
    $sid  = session_id();
    $ip   = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!$tipo) jsonResponse(['ok' => false]);

    $pdo->prepare("INSERT INTO analytics (tipo,referencia,session_id,usuario_id,ip) VALUES (?,?,?,?,?)")
        ->execute([$tipo, $ref, $sid, $uid, $ip]);

    // Si es vista de producto, registrar en fomo
    if ($tipo === 'vista_producto' && $ref) {
        $pdo->prepare("INSERT INTO fomo_eventos (producto_id,tipo) VALUES (?,?)")
            ->execute([(int)$ref, 'vista']);
    }

    jsonResponse(['ok' => true]);
}

function getFomo(PDO $pdo): void {
    $pid = (int)($_GET['producto_id'] ?? 0);
    if (!$pid) jsonResponse([]);

    // Compras recientes de este producto
    $compras = $pdo->prepare("
        SELECT f.nombre, f.ciudad, f.creado_en
        FROM fomo_eventos f
        WHERE f.producto_id=? AND f.tipo='compra'
        AND f.creado_en >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY f.creado_en DESC LIMIT 5
    ");
    $compras->execute([$pid]);

    // Cuantos viendo ahora (ultimos 10 min)
    $viendo = $pdo->prepare("
        SELECT COUNT(DISTINCT session_id) as total
        FROM analytics
        WHERE tipo='vista_producto' AND referencia=?
        AND creado_en >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $viendo->execute([$pid]);

    jsonResponse([
        'compras_recientes' => $compras->fetchAll(),
        'viendo_ahora'      => (int)$viendo->fetchColumn()
    ]);
}

function getViendo(PDO $pdo): void {
    $pid = (int)($_GET['producto_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) as total FROM analytics WHERE tipo='vista_producto' AND referencia=? AND creado_en >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->execute([$pid]);
    jsonResponse(['total' => (int)$stmt->fetchColumn()]);
}

function guardarCarrito(PDO $pdo): void {
    $uid   = $_SESSION['usuario_id'] ?? null;
    $email = trim($_POST['email'] ?? '');
    $items = $_POST['items'] ?? '[]';
    $total = (int)($_POST['total'] ?? 0);
    $sid   = session_id();

    if (!$uid && !$email) jsonResponse(['ok' => false]);
    if ($total <= 0) jsonResponse(['ok' => false]);

    $check = $pdo->prepare("SELECT id FROM carritos_sesiones WHERE token_sesion = ? OR (id_usuario = ? AND id_usuario IS NOT NULL)");
    $check->execute([$sid, $uid]);
    $existe = $check->fetch();

    if ($existe) {
        $pdo->prepare("UPDATE carritos_sesiones SET datos_carrito=?, email_invitado=?, estado='activo', fecha_actualizacion=NOW() WHERE id=?")
            ->execute([$items, $email, $existe['id']]);
    } else {
        $pdo->prepare("INSERT INTO carritos_sesiones (token_sesion, id_usuario, email_invitado, datos_carrito) VALUES (?,?,?,?)")
            ->execute([$sid, $uid, $email, $items]);
    }
    
    jsonResponse(['ok' => true]);
}

function obtenerCarrito(PDO $pdo): void {
    $uid = $_SESSION['usuario_id'] ?? null;
    $sid = session_id();

    $stmt = $pdo->prepare("SELECT datos_carrito FROM carritos_sesiones WHERE (id_usuario=? OR token_sesion=?) AND estado != 'completado' ORDER BY fecha_actualizacion DESC LIMIT 1");
    $stmt->execute([$uid, $sid]);
    $row = $stmt->fetch();
    
    jsonResponse(['items' => $row ? json_decode($row['datos_carrito']) : null]);
}
