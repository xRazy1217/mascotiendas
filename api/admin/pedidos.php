<?php
// Controlador de Pedidos para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
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
