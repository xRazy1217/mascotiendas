<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$action = $data['action'] ?? 'subscribe';

$pdo = getPDO();

if ($action === 'subscribe') {
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $nombre = htmlspecialchars($data['nombre'] ?? '');
    
    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'Email inválido']);
        exit;
    }
    
    try {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO newsletter_suscriptores (email, nombre, token_desuscripcion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE estado = 'activo', nombre = VALUES(nombre)");
        $stmt->execute([$email, $nombre, $token]);
        echo json_encode(['success' => true, 'message' => '¡Suscripción exitosa!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al suscribirse. Posiblemente ya estés registrado.']);
    }
} elseif ($action === 'unsubscribe') {
    $token = $data['token'] ?? '';
    if (!$token) {
        echo json_encode(['success' => false, 'error' => 'Token requerido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE newsletter_suscriptores SET estado = 'desuscrito', fecha_desuscripcion = NOW() WHERE token_desuscripcion = ?");
        $stmt->execute([$token]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Te has desuscrito correctamente.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Token inválido o ya desuscrito.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Acción inválida']);
}
