<?php
// Ejecuta este archivo UNA VEZ para crear el admin
// http://localhost/mascotiendas/crear_admin.php
// ELIMINALO despues

require_once 'config/db.php';

$pdo  = getPDO();
$hash = password_hash('Admin1234!', PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare("
    INSERT INTO usuarios (nombre, apellido, email, password_hash, rol)
    VALUES ('Mascotiendas', 'Admin', 'admin@mascotiendas.cl', ?, 'admin')
    ON DUPLICATE KEY UPDATE password_hash = ?
");
$stmt->execute([$hash, $hash]);

echo "<p>Admin creado/actualizado correctamente.</p>";
echo "<p>Email: <strong>admin@mascotiendas.cl</strong></p>";
echo "<p>Password: <strong>Admin1234!</strong></p>";
echo "<p style='color:red'><strong>ELIMINA ESTE ARCHIVO AHORA.</strong></p>";
