<?php
// Controlador de Reseñas para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
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
