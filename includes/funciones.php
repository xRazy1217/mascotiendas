<?php
require_once __DIR__ . '/../config/db.php';

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function authRequired(): array {
    session_start();
    if (empty($_SESSION['usuario_id'])) {
        jsonResponse(['error' => 'No autenticado'], 401);
    }
    return ['id' => $_SESSION['usuario_id'], 'rol' => $_SESSION['rol']];
}

function adminRequired(): void {
    $u = authRequired();
    if ($u['rol'] !== 'admin') jsonResponse(['error' => 'Sin permisos'], 403);
}

function formatPrecio(int $precio): string {
    return '$' . number_format($precio, 0, ',', '.');
}

function imagenPrincipal(int $productoId): string {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT url FROM producto_imagenes WHERE producto_id = ? AND posicion = 0 LIMIT 1");
    $stmt->execute([$productoId]);
    $row = $stmt->fetch();
    return $row ? $row['url'] : '/mascotiendas/assets/no-image.png';
}

function sanitize(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
