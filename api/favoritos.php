<?php
require_once __DIR__ . '/../includes/funciones.php';

session_name('mascotiendas');
ini_set('session.cookie_path', '/');
session_start();

$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'toggle'        => toggleFavorito($pdo),
    'lista'         => listaFavoritos($pdo),
    'aviso_stock'   => avisarStock($pdo),
    default         => jsonResponse(['error' => 'Accion no valida'], 400)
};

function toggleFavorito(PDO $pdo): void {
    if (empty($_SESSION['usuario_id'])) jsonResponse(['error' => 'Debes iniciar sesion'], 401);
    $uid = $_SESSION['usuario_id'];
    $pid = (int)($_POST['producto_id'] ?? 0);
    if (!$pid) jsonResponse(['error' => 'producto_id requerido'], 400);

    $check = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id=? AND producto_id=?");
    $check->execute([$uid, $pid]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?")->execute([$uid, $pid]);
        jsonResponse(['ok' => true, 'favorito' => false]);
    } else {
        $pdo->prepare("INSERT INTO favoritos (usuario_id, producto_id) VALUES (?,?)")->execute([$uid, $pid]);
        jsonResponse(['ok' => true, 'favorito' => true]);
    }
}

function listaFavoritos(PDO $pdo): void {
    if (empty($_SESSION['usuario_id'])) jsonResponse([]);
    $stmt = $pdo->prepare("
        SELECT p.id, p.nombre, p.precio_normal, p.precio_rebajado, p.en_stock,
               (SELECT url FROM producto_imagenes WHERE producto_id=p.id AND posicion=0 LIMIT 1) as imagen
        FROM favoritos f JOIN productos p ON f.producto_id=p.id
        WHERE f.usuario_id=? AND p.activo=1 ORDER BY f.creado_en DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    jsonResponse($stmt->fetchAll());
}

function avisarStock(PDO $pdo): void {
    $pid   = (int)($_POST['producto_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    if (!$pid || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Datos invalidos'], 400);
    $uid = $_SESSION['usuario_id'] ?? null;
    $pdo->prepare("INSERT IGNORE INTO avisos_stock (producto_id, email, usuario_id) VALUES (?,?,?)")->execute([$pid, $email, $uid]);
    jsonResponse(['ok' => true]);
}
