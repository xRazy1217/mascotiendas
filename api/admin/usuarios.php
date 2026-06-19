<?php
// Controlador de Usuarios para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
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
