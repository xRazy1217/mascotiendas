<?php
require_once __DIR__ . '/../includes/funciones.php';
checkCSRF();
$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'login'    => login($pdo),
    'registro' => registro($pdo),
    'logout'   => logout(),
    'perfil'   => perfil($pdo),
    'update'   => updatePerfil($pdo),
    default    => jsonResponse(['error' => 'Acción no válida'], 400)
};

function login(PDO $pdo): void {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) jsonResponse(['error' => 'Datos incompletos'], 400);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        jsonResponse(['error' => 'Credenciales incorrectas'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['rol']        = $user['rol'];
    $_SESSION['nombre']     = $user['nombre'];

    jsonResponse(['ok' => true, 'nombre' => $user['nombre'], 'rol' => $user['rol']]);
}

function registro(PDO $pdo): void {
    $nombre   = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $tel      = sanitize($_POST['telefono'] ?? '');

    if (!$nombre || !$email || !$pass) jsonResponse(['error' => 'Datos incompletos'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Email inválido'], 400);
    if (strlen($pass) < 6) jsonResponse(['error' => 'Contraseña mínimo 6 caracteres'], 400);

    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) jsonResponse(['error' => 'Email ya registrado'], 409);

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, password_hash, telefono) VALUES (?,?,?,?,?)");
    $stmt->execute([$nombre, $apellido, $email, $hash, $tel]);

    $id = (int)$pdo->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $id;
    $_SESSION['rol']        = 'cliente';
    $_SESSION['nombre']     = $nombre;

    jsonResponse(['ok' => true, 'nombre' => $nombre, 'rol' => 'cliente']);
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['ok' => true]);
}

function perfil(PDO $pdo): void {
    if (empty($_SESSION['usuario_id'])) jsonResponse(['logueado' => false]);

    $stmt = $pdo->prepare("SELECT id, nombre, apellido, email, telefono, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();

    $dirs = $pdo->prepare("SELECT * FROM direcciones WHERE usuario_id = ? ORDER BY predeterminada DESC");
    $dirs->execute([$_SESSION['usuario_id']]);

    $pedidos = $pdo->prepare("SELECT id, total, estado, creado_en FROM pedidos WHERE usuario_id = ? ORDER BY creado_en DESC LIMIT 10");
    $pedidos->execute([$_SESSION['usuario_id']]);

    jsonResponse(['logueado' => true, 'usuario' => $user, 'direcciones' => $dirs->fetchAll(), 'pedidos' => $pedidos->fetchAll()]);
}

function updatePerfil(PDO $pdo): void {
    if (empty($_SESSION['usuario_id'])) jsonResponse(['error' => 'No autenticado'], 401);

    $nombre   = sanitize($_POST['nombre'] ?? '');
    $apellido = sanitize($_POST['apellido'] ?? '');
    $tel      = sanitize($_POST['telefono'] ?? '');

    $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, apellido=?, telefono=? WHERE id=?");
    $stmt->execute([$nombre, $apellido, $tel, $_SESSION['usuario_id']]);
    $_SESSION['nombre'] = $nombre;

    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("UPDATE usuarios SET password_hash=? WHERE id=?")->execute([$hash, $_SESSION['usuario_id']]);
    }

    jsonResponse(['ok' => true]);
}
