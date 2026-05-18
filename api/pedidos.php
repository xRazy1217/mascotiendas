<?php
require_once __DIR__ . '/../includes/funciones.php';

session_name('mascotiendas');
ini_set('session.cookie_path', '/');
session_start();
$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'crear'    => crearPedido($pdo),
    'detalle'  => detallePedido($pdo),
    'mis'      => misPedidos($pdo),
    'newsletter' => newsletter($pdo),
    default    => jsonResponse(['error' => 'Acción no válida'], 400)
};

function crearPedido(PDO $pdo): void {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) jsonResponse(['error' => 'Datos inválidos'], 400);

    $nombre         = sanitize($body['nombre'] ?? '');
    $email          = trim($body['email'] ?? '');
    $tel            = sanitize($body['telefono'] ?? '');
    $dir            = sanitize($body['direccion'] ?? '');
    $ciudad         = sanitize($body['ciudad'] ?? '');
    $sucursal       = sanitize($body['sucursal_retiro'] ?? $body['sucursal'] ?? '');
    $notas          = sanitize($body['notas'] ?? '');
    $metodo_entrega = sanitize($body['metodo_entrega'] ?? 'delivery');
    $metodo_pago    = sanitize($body['metodo_pago'] ?? 'transferencia');
    $items          = $body['items'] ?? [];

    if (!$nombre || !$email || empty($items)) {
        jsonResponse(['error' => 'Datos incompletos'], 400);
    }
    if ($metodo_entrega === 'delivery' && (!$dir || !$ciudad)) {
        jsonResponse(['error' => 'Dirección y ciudad requeridas para delivery'], 400);
    }

    // Verificar precios desde BD (nunca confiar en el cliente)
    $subtotal = 0;
    $itemsVerificados = [];
    foreach ($items as $item) {
        $id  = (int)($item['id'] ?? 0);
        $qty = max(1, (int)($item['cantidad'] ?? 1));
        $stmt = $pdo->prepare("SELECT id, nombre, precio_rebajado, precio_normal, en_stock, (SELECT url FROM producto_imagenes WHERE producto_id = p.id AND posicion = 0 LIMIT 1) as imagen FROM productos p WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod || !$prod['en_stock']) continue;
        $precio = $prod['precio_rebajado'] ?? $prod['precio_normal'];
        $subtotal += $precio * $qty;
        $itemsVerificados[] = ['producto_id' => $id, 'nombre' => $prod['nombre'], 'precio' => $precio, 'cantidad' => $qty, 'imagen_url' => $prod['imagen']];
    }

    if (empty($itemsVerificados)) jsonResponse(['error' => 'Carrito vacío o productos no disponibles'], 400);

    $usuarioId = $_SESSION['usuario_id'] ?? null;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, nombre_cliente, email_cliente, telefono, direccion, ciudad, sucursal, subtotal, total, notas, metodo_pago, metodo_entrega) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$usuarioId, $nombre, $email, $tel, $dir, $ciudad, $sucursal, $subtotal, $subtotal, $notas, $metodo_pago, $metodo_entrega]);
        $pedidoId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare("INSERT INTO pedido_items (pedido_id, producto_id, nombre, precio, cantidad, imagen_url) VALUES (?,?,?,?,?,?)");
        foreach ($itemsVerificados as $it) {
            $ins->execute([$pedidoId, $it['producto_id'], $it['nombre'], $it['precio'], $it['cantidad'], $it['imagen_url']]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Error al crear pedido'], 500);
    }

    jsonResponse(['ok' => true, 'pedido_id' => $pedidoId, 'total' => $subtotal]);
}

function detallePedido(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $usuarioId = $_SESSION['usuario_id'] ?? null;
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();

    if (!$pedido) jsonResponse(['error' => 'No encontrado'], 404);
    if ($usuarioId && $pedido['usuario_id'] != $usuarioId) jsonResponse(['error' => 'Sin acceso'], 403);

    $items = $pdo->prepare("SELECT * FROM pedido_items WHERE pedido_id = ?");
    $items->execute([$id]);
    $pedido['items'] = $items->fetchAll();

    jsonResponse($pedido);
}

function misPedidos(PDO $pdo): void {
    if (empty($_SESSION['usuario_id'])) jsonResponse(['error' => 'No autenticado'], 401);

    $stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM pedido_items WHERE pedido_id = p.id) as total_items FROM pedidos p WHERE p.usuario_id = ? ORDER BY p.creado_en DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    jsonResponse($stmt->fetchAll());
}

function newsletter(PDO $pdo): void {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Email inválido'], 400);
    $pdo->prepare("INSERT IGNORE INTO newsletter (email) VALUES (?)")->execute([$email]);
    jsonResponse(['ok' => true]);
}
