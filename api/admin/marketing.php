<?php
// Controlador de Marketing, Cupones y Newsletter para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
}

// ─── CUPONES ─────────────────────────────────────────────
function cuponesList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM cupones ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function cuponSave(PDO $pdo): void {
    $id       = (int)($_POST['id'] ?? 0);
    $codigo   = strtoupper(sanitize($_POST['codigo'] ?? ''));
    $tipo     = $_POST['tipo'] ?? 'porcentaje';
    $valor    = (int)($_POST['valor'] ?? 0);
    $minimo   = (int)($_POST['minimo_compra'] ?? 0);
    $usos_max = !empty($_POST['usos_max']) ? (int)$_POST['usos_max'] : null;
    $activo   = (int)($_POST['activo'] ?? 1);
    $expira   = !empty($_POST['expira_en']) ? $_POST['expira_en'] : null;

    if (!$codigo || !$valor) jsonResponse(['error' => 'Codigo y valor requeridos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE cupones SET codigo=?,tipo=?,valor=?,minimo_compra=?,usos_max=?,activo=?,expira_en=? WHERE id=?")
            ->execute([$codigo,$tipo,$valor,$minimo,$usos_max,$activo,$expira,$id]);
    } else {
        $pdo->prepare("INSERT INTO cupones (codigo,tipo,valor,minimo_compra,usos_max,activo,expira_en) VALUES (?,?,?,?,?,?,?)")
            ->execute([$codigo,$tipo,$valor,$minimo,$usos_max,$activo,$expira]);
    }
    jsonResponse(['ok' => true]);
}

function cuponDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM cupones WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function cuponValidar(PDO $pdo): void {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $total  = (int)($_POST['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM cupones WHERE codigo=? AND activo=1");
    $stmt->execute([$codigo]);
    $cupon = $stmt->fetch();

    if (!$cupon) jsonResponse(['error' => 'Cupon no valido'], 404);
    if ($cupon['expira_en'] && $cupon['expira_en'] < date('Y-m-d')) jsonResponse(['error' => 'Cupon expirado'], 400);
    if ($cupon['usos_max'] && $cupon['usos_actual'] >= $cupon['usos_max']) jsonResponse(['error' => 'Cupon agotado'], 400);
    if ($total < $cupon['minimo_compra']) jsonResponse(['error' => 'Minimo de compra: $'.number_format($cupon['minimo_compra'],0,',','.')], 400);

    $descuento = $cupon['tipo'] === 'porcentaje'
        ? (int)round($total * $cupon['valor'] / 100)
        : min($cupon['valor'], $total);

    jsonResponse(['ok' => true, 'cupon' => $cupon, 'descuento' => $descuento]);
}

// ─── MARKETING / NEWSLETTER ─────────────────────────────────────────
function newsletterList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM newsletter_suscriptores ORDER BY fecha_registro DESC");
    jsonResponse($stmt->fetchAll());
}

function newsletterSuscriptorToggle(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
    
    $stmt = $pdo->prepare("SELECT estado FROM newsletter_suscriptores WHERE id = ?");
    $stmt->execute([$id]);
    $suscriptor = $stmt->fetch();
    if (!$suscriptor) jsonResponse(['error' => 'No encontrado'], 404);
    
    $nuevoEstado = $suscriptor['estado'] === 'activo' ? 'desuscrito' : 'activo';
    $fechaDes = ($nuevoEstado === 'desuscrito') ? date('Y-m-d H:i:s') : null;
    
    $pdo->prepare("UPDATE newsletter_suscriptores SET estado = ?, fecha_desuscripcion = ? WHERE id = ?")
        ->execute([$nuevoEstado, $fechaDes, $id]);
        
    jsonResponse(['ok' => true, 'nuevo_estado' => $nuevoEstado]);
}

function newsletterSuscriptorDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
    
    $pdo->prepare("DELETE FROM newsletter_suscriptores WHERE id = ?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function newsletterEnviarCampana(PDO $pdo): void {
    $asunto = sanitize($_POST['asunto'] ?? '');
    $mensaje = $_POST['mensaje'] ?? ''; 
    
    if (!$asunto || !$mensaje) jsonResponse(['error' => 'Asunto y mensaje requeridos'], 400);
    
    $stmt = $pdo->query("SELECT email, nombre, token_desuscripcion FROM newsletter_suscriptores WHERE estado = 'activo'");
    $suscriptores = $stmt->fetchAll();
    
    if (empty($suscriptores)) {
        jsonResponse(['error' => 'No hay suscriptores activos para enviar la campaña.'], 400);
    }
    
    $enviados = 0;
    
    $stmt_campana = $pdo->prepare("INSERT INTO newsletter_campanas (asunto, cuerpo_html, estado, fecha_envio) VALUES (?, ?, 'enviada', NOW())");
    $stmt_campana->execute([$asunto, $mensaje]);
    $campanaId = $pdo->lastInsertId();
    
    foreach ($suscriptores as $s) {
        $email = $s['email'];
        $nombre = $s['nombre'] ?: 'PetLover';
        $token = $s['token_desuscripcion'];
        
        $enlaceDesuscripcion = "https://mascotiendas.cl/api/newsletter.php?action=unsubscribe&token=" . $token;
        
        $mensajePersonalizado = str_replace('{nombre}', $nombre, $mensaje);
        $mensajePersonalizado .= "<br><br>---<br>Si deseas dejar de recibir estos correos, puedes desuscribirte haciendo clic aquí: <a href='" . $enlaceDesuscripcion . "'>" . $enlaceDesuscripcion . "</a>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Mascotiendas <ventas@mascotiendas.cl>" . "\r\n";
        $headers .= "Reply-To: ventas@mascotiendas.cl" . "\r\n";
        
        if (@mail($email, $asunto, $mensajePersonalizado, $headers)) {
            $enviados++;
        }
    }
    
    $pdo->prepare("UPDATE newsletter_campanas SET enviados = ? WHERE id = ?")->execute([$enviados, $campanaId]);
    
    jsonResponse(['ok' => true, 'mensaje' => "Campaña enviada a $enviados suscriptores."]);
}

function carritoEnviarRecordatorio(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $tipo = (int)($_POST['tipo'] ?? 1);
    
    if (!$id || !in_array($tipo, [1, 2])) jsonResponse(['error' => 'Parámetros inválidos'], 400);
    
    $stmt = $pdo->prepare("SELECT * FROM carritos_sesiones WHERE id = ?");
    $stmt->execute([$id]);
    $cart = $stmt->fetch();
    
    if (!$cart) jsonResponse(['error' => 'Carrito no encontrado'], 404);
    
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
    
    if (!$email) jsonResponse(['error' => 'No hay email asociado a este carrito'], 400);
    
    // Verificar si el email está en la lista negra
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM marketing_blacklist WHERE email = ?");
    $stmt_check->execute([$email]);
    if ($stmt_check->fetchColumn() > 0) {
        jsonResponse(['error' => 'Este cliente ha solicitado no recibir correos de marketing.'], 400);
    }

    $isLocal = (strpos(__DIR__, 'wamp64') !== false || strpos(__DIR__, 'xampp') !== false);
    $domain = $isLocal ? 'http://localhost/mascotiendas' : 'https://mascotiendas.cl';
    $optoutToken = md5($email . 'mascotiendas_marketing_salt_987');
    $optoutLink = $domain . "/api/marketing.php?action=optout&email=" . urlencode($email) . "&token=" . $optoutToken;

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Mascotiendas <ventas@mascotiendas.cl>" . "\r\n";
    $headers .= "Reply-To: ventas@mascotiendas.cl" . "\r\n";
    
    if ($tipo === 1) {
        $asunto = "🐾 ¿Olvidaste algo para tu mascota?";
        $mensaje = "Hola $nombre!<br><br>Notamos que dejaste algunos productos excelentes en tu carrito de Mascotiendas. ¡Todavía te están esperando y tenemos stock disponible!<br><br>Regresa a completar tu compra rápidamente en: <a href='$domain/?p=carrito'>$domain/?p=carrito</a><br><br>El equipo de Mascotiendas.<br><br>---<br><span style='font-size:11px;color:#999;'>Si deseas dejar de recibir correos automáticos de recordatorios de compra, haz clic en el siguiente enlace: <a href='$optoutLink' style='color:#F7941D;'>Desuscribirse aquí</a></span>";
        @mail($email, $asunto, $mensaje, $headers);
        
        $pdo->prepare("UPDATE carritos_sesiones SET recordatorio_1_enviado = 1, estado = 'abandonado' WHERE id = ?")->execute([$id]);
    } else {
        $asunto = "🎁 ¡Tenemos un regalo para tu peludo!";
        $mensaje = "Hola $nombre!<br><br>Vimos que no completaste tu compra. ¡No queremos que te quedes sin tus productos!<br><br>Usa el código <strong>VUELVE10</strong> para obtener un descuento especial del 10% en tu carrito actual. ¡Canjéalo ahora en: <a href='$domain/?p=carrito'>$domain/?p=carrito</a>!<br><br>¡Aprovecha antes de que se acabe el stock!<br><br>---<br><span style='font-size:11px;color:#999;'>Si deseas dejar de recibir correos automáticos de recordatorios de compra, haz clic en el siguiente enlace: <a href='$optoutLink' style='color:#F7941D;'>Desuscribirse aquí</a></span>";
        @mail($email, $asunto, $mensaje, $headers);
        
        $pdo->prepare("UPDATE carritos_sesiones SET recordatorio_2_enviado = 1, estado = 'abandonado' WHERE id = ?")->execute([$id]);
    }
    
    jsonResponse(['ok' => true, 'mensaje' => "Recordatorio $tipo enviado exitosamente."]);
}

// ─── CAMPANAS EMERGENTES ────────────────────────────────────────
function campanasList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM campanas ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function campanaSave(PDO $pdo): void {
    $id        = (int)($_POST['id'] ?? 0);
    $nombre    = sanitize($_POST['nombre'] ?? '');
    $titulo    = sanitize($_POST['titulo'] ?? '');
    $texto     = trim($_POST['texto'] ?? '');
    $imagen    = sanitize($_POST['imagen_url'] ?? '');
    $btn_texto = sanitize($_POST['btn_texto'] ?? '');
    $btn_url   = sanitize($_POST['btn_url'] ?? '');
    $activacion= $_POST['activacion'] ?? 'exit';
    $segundos  = (int)($_POST['segundos'] ?? 5);
    $fecha_ini = !empty($_POST['fecha_ini']) ? $_POST['fecha_ini'] : null;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $activo    = (int)($_POST['activo'] ?? 1);

    if (!$nombre || !$titulo) jsonResponse(['error' => 'Nombre y título requeridos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE campanas SET nombre=?,titulo=?,texto=?,imagen_url=?,btn_texto=?,btn_url=?,activacion=?,segundos=?,fecha_ini=?,fecha_fin=?,activo=? WHERE id=?")
            ->execute([$nombre,$titulo,$texto,$imagen,$btn_texto,$btn_url,$activacion,$segundos,$fecha_ini,$fecha_fin,$activo,$id]);
    } else {
        $pdo->prepare("INSERT INTO campanas (nombre,titulo,texto,imagen_url,btn_texto,btn_url,activacion,segundos,fecha_ini,fecha_fin,activo) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$nombre,$titulo,$texto,$imagen,$btn_texto,$btn_url,$activacion,$segundos,$fecha_ini,$fecha_fin,$activo]);
    }
    jsonResponse(['ok' => true]);
}

function campanaDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM campanas WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

function campanaToggle(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE campanas SET activo = 1 - activo WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}

// ─── BLACKLIST (OPT-OUT) ────────────────────────────────────────
function blacklistList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM marketing_blacklist ORDER BY fecha_desuscripcion DESC");
    jsonResponse($stmt->fetchAll());
}

function blacklistDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
    
    $pdo->prepare("DELETE FROM marketing_blacklist WHERE id = ?")->execute([$id]);
    jsonResponse(['ok' => true]);
}
