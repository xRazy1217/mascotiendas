<?php
// api/cron_carritos.php - Script para ejecutar vía CRON Job
// Configurar en CPanel: "0 * * * * php /ruta/api/cron_carritos.php"
require_once __DIR__ . '/../config/db.php';
$pdo = getPDO();

// Restricción de acceso: Solo CLI, localhost o con token válido
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
$isCLI = php_sapi_name() === 'cli';
$cronToken = $_GET['token'] ?? '';
$configuredToken = 'mascotiendas_cron_secure_token_abc123';

if (!$isCLI && !$isLocalhost && $cronToken !== $configuredToken) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Cron de carritos no autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 0. Verificar si la automatización de marketing está desactivada globalmente
$stmt_config = $pdo->query("SELECT valor FROM configuraciones WHERE clave = 'marketing_automatizacion_activo'");
$marketingActivo = $stmt_config->fetchColumn();
if ($marketingActivo === '0') {
    echo json_encode([
        'success' => false,
        'message' => 'La automatización de marketing está desactivada globalmente.'
    ]);
    exit;
}

// 1. Marcar como abandonados los carritos con más de 2 horas sin actividad
$pdo->exec("UPDATE carritos_sesiones SET estado = 'abandonado' WHERE estado = 'activo' AND fecha_actualizacion < DATE_SUB(NOW(), INTERVAL 2 HOUR)");

// 2. Enviar Recordatorio 1 (A los carritos abandonados que no tienen el recordatorio enviado)
$stmt1 = $pdo->query("SELECT * FROM carritos_sesiones WHERE estado = 'abandonado' AND recordatorio_1_enviado = 0 AND (id_usuario IS NOT NULL OR email_invitado IS NOT NULL)");
$carritos1 = $stmt1->fetchAll();

foreach ($carritos1 as $cart) {
    $email = $cart['email_invitado'];
    $nombre = "PetLover";
    
    if (!$email && $cart['id_usuario']) {
        $stmt_user = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id = ?");
        $stmt_user->execute([$cart['id_usuario']]);
        $user = $stmt_user->fetch();
        if ($user) {
            $email = $user['email'];
            $nombre = $user['nombre'];
        }
    }
    
    if ($email) {
        // Verificar si el email está en la lista negra
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM marketing_blacklist WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) {
            // Se marca como enviado para no procesarlo más
            $pdo->prepare("UPDATE carritos_sesiones SET recordatorio_1_enviado = 1 WHERE id = ?")->execute([$cart['id']]);
            continue;
        }

        $isLocal = (strpos(__DIR__, 'wamp64') !== false || strpos(__DIR__, 'xampp') !== false);
        $domain = $isLocal ? 'http://localhost/mascotiendas' : 'https://mascotiendas.cl';
        $optoutToken = md5($email . 'mascotiendas_marketing_salt_987');
        $optoutLink = $domain . "/api/marketing.php?action=optout&email=" . urlencode($email) . "&token=" . $optoutToken;

        $asunto = "🐾 ¿Olvidaste algo para tu mascota?";
        $mensaje = "Hola $nombre!\n\nNotamos que dejaste algunos productos excelentes en tu carrito de Mascotiendas. ¡Todavía te están esperando y tenemos stock disponible!\n\nRegresa a completar tu compra rápidamente en: $domain/?p=carrito\n\nSi necesitas ayuda, puedes responder este correo o escribirnos al WhatsApp.\n\nEl equipo de Mascotiendas.\n\n---\nSi deseas dejar de recibir correos automáticos de recordatorios de compra, haz clic en el siguiente enlace: $optoutLink";
        
        $headers = "From: Mascotiendas <ventas@mascotiendas.cl>\r\n";
        $headers .= "Reply-To: ventas@mascotiendas.cl\r\n";
        
        // Simulación de envío (Aquí va mail() o el cliente SMTP real)
        @mail($email, $asunto, $mensaje, $headers);
        
        $pdo->prepare("UPDATE carritos_sesiones SET recordatorio_1_enviado = 1 WHERE id = ?")->execute([$cart['id']]);
    }
}

// 3. Enviar Recordatorio 2 con Descuento (A los abandonados con más de 24 horas)
$stmt2 = $pdo->query("SELECT * FROM carritos_sesiones WHERE estado = 'abandonado' AND recordatorio_1_enviado = 1 AND recordatorio_2_enviado = 0 AND fecha_actualizacion < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND (id_usuario IS NOT NULL OR email_invitado IS NOT NULL)");
$carritos2 = $stmt2->fetchAll();

foreach ($carritos2 as $cart) {
    $email = $cart['email_invitado'];
    if (!$email && $cart['id_usuario']) {
        $stmt_user = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id = ?");
        $stmt_user->execute([$cart['id_usuario']]);
        if ($user = $stmt_user->fetch()) {
            $email = $user['email'];
        }
    }
    
    if ($email) {
        // Verificar si el email está en la lista negra
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM marketing_blacklist WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) {
            // Se marca como enviado para no procesarlo más
            $pdo->prepare("UPDATE carritos_sesiones SET recordatorio_2_enviado = 1 WHERE id = ?")->execute([$cart['id']]);
            continue;
        }

        $isLocal = (strpos(__DIR__, 'wamp64') !== false || strpos(__DIR__, 'xampp') !== false);
        $domain = $isLocal ? 'http://localhost/mascotiendas' : 'https://mascotiendas.cl';
        $optoutToken = md5($email . 'mascotiendas_marketing_salt_987');
        $optoutLink = $domain . "/api/marketing.php?action=optout&email=" . urlencode($email) . "&token=" . $optoutToken;

        $asunto = "🎁 ¡Tenemos un regalo para tu peludo!";
        $mensaje = "Hola!\n\nVimos que no completaste tu compra. ¡No queremos que te quedes sin tus productos!\n\nUsa el código VUELVE10 para obtener un descuento especial en tu carrito actual. ¡Canjéalo ahora en $domain/?p=carrito!\n\n¡Aprovecha antes de que se acabe el stock!\n\n---\nSi deseas dejar de recibir correos automáticos de recordatorios de compra, haz clic en el siguiente enlace: $optoutLink";
        $headers = "From: Mascotiendas <ventas@mascotiendas.cl>\r\n";
        @mail($email, $asunto, $mensaje, $headers);
        
        $pdo->prepare("UPDATE carritos_sesiones SET recordatorio_2_enviado = 1 WHERE id = ?")->execute([$cart['id']]);
    }
}

echo json_encode([
    'success' => true, 
    'message' => 'CRON de carritos ejecutado exitosamente', 
    'marcados_abandonados' => 'Ok',
    'recordatorios_1_enviados' => count($carritos1), 
    'recordatorios_2_enviados' => count($carritos2)
]);
