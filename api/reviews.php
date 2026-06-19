<?php
require_once __DIR__ . '/../includes/funciones.php';
checkCSRF();

$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'crear'        => crearReview($pdo),
    'del_producto' => reviewsProducto($pdo),
    default        => jsonResponse(['error' => 'Accion no valida'], 400)
};

function crearReview(PDO $pdo): void {
    $pid       = (int)($_POST['producto_id'] ?? 0);
    $nombre    = sanitize($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $estrellas = max(1, min(5, (int)($_POST['estrellas'] ?? 5)));
    $comentario = sanitize($_POST['comentario'] ?? '');
    $uid       = $_SESSION['usuario_id'] ?? null;

    // Hardening: Limitar longitud de texto para evitar abusos de tamaño en base de datos
    $nombre = substr($nombre, 0, 100);
    $comentario = substr($comentario, 0, 1000);

    if (!$pid || !$nombre || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Datos incompletos'], 400);
    }

    // Verificar que no haya review duplicada del mismo email para este producto
    $check = $pdo->prepare("SELECT id FROM reviews WHERE producto_id=? AND email=?");
    $check->execute([$pid, $email]);
    if ($check->fetch()) jsonResponse(['error' => 'Ya dejaste una reseña para este producto'], 409);

    $pdo->prepare("INSERT INTO reviews (producto_id,usuario_id,nombre,email,estrellas,comentario) VALUES (?,?,?,?,?,?)")
        ->execute([$pid, $uid, $nombre, $email, $estrellas, $comentario]);

    jsonResponse(['ok' => true, 'mensaje' => 'Reseña enviada, será publicada tras revisión']);
}

function reviewsProducto(PDO $pdo): void {
    $pid = (int)($_GET['producto_id'] ?? 0);
    if (!$pid) jsonResponse([]);

    $stmt = $pdo->prepare("
        SELECT id, nombre, estrellas, comentario, creado_en
        FROM reviews WHERE producto_id=? AND aprobado=1
        ORDER BY creado_en DESC LIMIT 20
    ");
    $stmt->execute([$pid]);
    $reviews = $stmt->fetchAll();

    $stats = $pdo->prepare("SELECT AVG(estrellas) as promedio, COUNT(*) as total FROM reviews WHERE producto_id=? AND aprobado=1");
    $stats->execute([$pid]);

    jsonResponse(['reviews' => $reviews, 'stats' => $stats->fetch()]);
}
