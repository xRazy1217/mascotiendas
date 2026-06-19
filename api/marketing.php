<?php
require_once __DIR__ . '/../includes/funciones.php';
$pdo = getPDO();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'optout') {
    $email = trim($_GET['email'] ?? '');
    $token = trim($_GET['token'] ?? '');
    
    if (!$email || !$token) {
        die("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Error: Parámetros inválidos.</h3>");
    }
    
    // Validar token de desuscripción
    $expectedToken = md5($email . 'mascotiendas_marketing_salt_987');
    if (!hash_equals($expectedToken, $token)) {
        die("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Error: Token de desuscripción inválido.</h3>");
    }
    
    try {
        // Agregar a la lista negra de marketing
        $stmt = $pdo->prepare("INSERT IGNORE INTO marketing_blacklist (email) VALUES (?)");
        $stmt->execute([$email]);
        
        // Desuscribir también del boletín de newsletter si estuviera registrado
        $stmt_news = $pdo->prepare("UPDATE newsletter_suscriptores SET estado = 'desuscrito', fecha_desuscripcion = NOW() WHERE email = ?");
        $stmt_news->execute([$email]);
        
        // Renderizar confirmación con diseño Premium
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Desuscripción Exitosa — Mascotiendas</title>
            <script src="../assets/vendor/tailwindcss.js"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'mt-brown': '#7F5234',
                                'mt-orange': '#F7941D',
                                'mt-cream': '#F9F1E7'
                            }
                        }
                    }
                }
            </script>
        </head>
        <body class="bg-mt-cream min-h-screen flex items-center justify-center p-4">
            <div class="bg-white p-8 rounded-3xl shadow-xl max-w-md w-full text-center border border-mt-orange/20">
                <div class="w-16 h-16 bg-mt-orange/10 rounded-full flex items-center justify-center mx-auto mb-6 text-mt-orange text-2xl">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h1 class="text-2xl font-black text-mt-brown mb-2 uppercase">Desuscripción Completada</h1>
                <p class="text-sm text-slate-500 leading-relaxed mb-6">
                    Hemos procesado tu solicitud. La dirección <strong class="text-mt-brown"><?= htmlspecialchars($email) ?></strong> ha sido removida de nuestras listas de correos automáticos de marketing y carritos abandonados.
                </p>
                <div class="p-4 bg-slate-50 rounded-2xl mb-6 text-xs text-slate-400">
                    No recibirás más recordatorios de compras pendientes ni boletines promocionales.
                </div>
                <a href="/" class="inline-block w-full py-3 bg-mt-orange text-white rounded-xl font-bold hover:bg-mt-brown transition-colors text-sm uppercase">
                    Volver a la Tienda
                </a>
            </div>
        </body>
        </html>
        <?php
        exit;
    } catch (PDOException $e) {
        die("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Error al procesar la desuscripción: " . htmlspecialchars($e->getMessage()) . "</h3>");
    }
} else {
    jsonResponse(['error' => 'Acción no válida'], 400);
}
