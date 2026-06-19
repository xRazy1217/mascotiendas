<?php
require_once __DIR__ . '/../includes/funciones.php';
checkCSRF();

if (empty($_SESSION['usuario_id'])) jsonResponse(['error' => 'No autenticado'], 401);

$pdo    = getPDO();
$uid    = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'guardar'        => guardarDireccion($pdo, $uid),
    'eliminar'       => eliminarDireccion($pdo, $uid),
    'predeterminada' => setPredeterminada($pdo, $uid),
    default          => jsonResponse(['error' => 'Accion no valida'], 400)
};

function guardarDireccion(PDO $pdo, int $uid): void {
    $id      = (int)($_POST['id'] ?? 0);
    $alias   = sanitize($_POST['alias'] ?? 'Casa');
    $calle   = sanitize($_POST['calle'] ?? '');
    $numero  = sanitize($_POST['numero'] ?? '');
    $depto   = sanitize($_POST['depto'] ?? '');
    $ciudad  = sanitize($_POST['ciudad'] ?? '');
    $region  = sanitize($_POST['region'] ?? '');
    $pred    = (int)($_POST['predeterminada'] ?? 0);

    if (!$calle || !$numero || !$ciudad) jsonResponse(['error' => 'Datos incompletos'], 400);

    if ($pred) {
        $pdo->prepare("UPDATE direcciones SET predeterminada=0 WHERE usuario_id=?")->execute([$uid]);
    }

    if ($id) {
        $pdo->prepare("UPDATE direcciones SET alias=?,calle=?,numero=?,depto=?,ciudad=?,region=?,predeterminada=? WHERE id=? AND usuario_id=?")
            ->execute([$alias,$calle,$numero,$depto,$ciudad,$region,$pred,$id,$uid]);
    } else {
        $pdo->prepare("INSERT INTO direcciones (usuario_id,alias,calle,numero,depto,ciudad,region,predeterminada) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$uid,$alias,$calle,$numero,$depto,$ciudad,$region,$pred]);
    }

    jsonResponse(['ok' => true]);
}

function eliminarDireccion(PDO $pdo, int $uid): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM direcciones WHERE id=? AND usuario_id=?")->execute([$id, $uid]);
    jsonResponse(['ok' => true]);
}

function setPredeterminada(PDO $pdo, int $uid): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE direcciones SET predeterminada=0 WHERE usuario_id=?")->execute([$uid]);
    $pdo->prepare("UPDATE direcciones SET predeterminada=1 WHERE id=? AND usuario_id=?")->execute([$id, $uid]);
    jsonResponse(['ok' => true]);
}
