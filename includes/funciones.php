<?php
require_once __DIR__ . '/../config/db.php';

// ─── CONFIGURACIÓN DE SESIONES SEGURAS ────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name('mascotiendas');
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar token CSRF para solicitudes de escritura (POST, PUT, DELETE, etc.)
function checkCSRF(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$token && function_exists('getallheaders')) {
            $headers = getallheaders();
            $token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
        }
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            jsonResponse(['error' => 'Acción no autorizada (CSRF Token inválido)'], 403);
        }
    }
}

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function authRequired(): array {
    checkCSRF();
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

function adjustBrightness(string $hex, int $steps): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
