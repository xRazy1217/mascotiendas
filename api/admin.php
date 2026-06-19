<?php
// Enrutador de API de Administración Modularizado

require_once __DIR__ . '/../includes/funciones.php';
checkCSRF();

if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    jsonResponse(['error' => 'Sin permisos'], 403);
}

$pdo    = getPDO();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Definir constante de control de ruta para submódulos
define('MASCOTIENDAS_ADMIN_ROUTE', true);

// Mapear cada acción administrativa a su respectivo archivo controlador modular
$controllerMap = [
    // Productos y Variantes
    'productos_list'          => 'productos.php',
    'producto_save'           => 'productos.php',
    'producto_delete'         => 'productos.php',
    'productos_accion_masiva' => 'productos.php',
    'imagen_upload'    => 'productos.php',
    'imagen_delete'    => 'productos.php',
    'imagen_crop_save' => 'productos.php',
    'variantes_list'   => 'productos.php',
    'variante_save'    => 'productos.php',
    'variante_delete'  => 'productos.php',
    'atributos_list'   => 'productos.php',

    // Pedidos
    'pedidos_list'     => 'pedidos.php',
    'pedido_estado'    => 'pedidos.php',
    'pedido_detalle'   => 'pedidos.php',

    // Usuarios
    'usuarios_list'    => 'usuarios.php',

    // Blog
    'blog_list'        => 'blog.php',
    'blog_save'        => 'blog.php',
    'blog_delete'      => 'blog.php',

    // Reviews
    'reviews_list'     => 'reviews.php',
    'review_aprobar'   => 'reviews.php',
    'review_delete'    => 'reviews.php',

    // Marketing, Newsletter, Cupones, Campañas
    'newsletter_list'              => 'marketing.php',
    'newsletter_suscriptor_toggle' => 'marketing.php',
    'newsletter_suscriptor_delete' => 'marketing.php',
    'newsletter_enviar_campana'    => 'marketing.php',
    'carrito_enviar_recordatorio'  => 'marketing.php',
    'cupones_list'                 => 'marketing.php',
    'cupon_save'                   => 'marketing.php',
    'cupon_delete'                 => 'marketing.php',
    'cupon_validar'                => 'marketing.php',
    'campanas_list'                => 'marketing.php',
    'campana_save'                 => 'marketing.php',
    'campana_delete'               => 'marketing.php',
    'campana_toggle'               => 'marketing.php',
    'blacklist_list'               => 'marketing.php',
    'blacklist_delete'             => 'marketing.php',

    // Dashboard, stats, notif, config, analytics
    'stats'                => 'config.php',
    'bajo_stock'           => 'config.php',
    'notif_list'           => 'config.php',
    'notif_leer'           => 'config.php',
    'notif_leer_todas'     => 'config.php',
    'analytics'            => 'config.php',
    'carritos_abandonados' => 'config.php',
    'config_get'           => 'config.php',
    'config_save'          => 'config.php',
    'textos_get'           => 'config.php',
    'texto_save'           => 'config.php',

    // Categorías
    'categorias_list'            => 'categorias.php',
    'categoria_save'             => 'categorias.php',
    'categoria_delete'           => 'categorias.php',
    'categoria_imagen_upload'    => 'categorias.php',

    // Comunidad
    'comunidad_list'             => 'comunidad.php',
    'comunidad_save'             => 'comunidad.php',
    'comunidad_delete'           => 'comunidad.php',
    'comunidad_imagen_upload'    => 'comunidad.php'
];

if (!$action || !isset($controllerMap[$action])) {
    jsonResponse(['error' => 'Accion no valida'], 400);
}

// Cargar el controlador correspondiente
require_once __DIR__ . '/admin/' . $controllerMap[$action];

// Despachar la acción al controlador
match($action) {
    // Productos y Variantes
    'productos_list'            => adminProductos($pdo),
    'producto_save'             => guardarProducto($pdo),
    'producto_delete'           => eliminarProducto($pdo),
    'productos_accion_masiva'   => productosAccionMasiva($pdo),
    'imagen_upload'   => subirImagen($pdo),
    'imagen_delete'   => eliminarImagen($pdo),
    'imagen_crop_save' => guardarRecorteImagen($pdo),
    'variantes_list'  => variantesList($pdo),
    'variante_save'   => varianteSave($pdo),
    'variante_delete' => varianteDelete($pdo),
    'atributos_list'  => atributosList($pdo),

    // Pedidos
    'pedidos_list'    => adminPedidos($pdo),
    'pedido_estado'   => cambiarEstado($pdo),
    'pedido_detalle'  => detallePedido($pdo),

    // Usuarios
    'usuarios_list'   => adminUsuarios($pdo),

    // Dashboard
    'stats'           => stats($pdo),
    'bajo_stock'      => bajoStock($pdo),

    // Notificaciones
    'notif_list'      => notifList($pdo),
    'notif_leer'      => notifLeer($pdo),
    'notif_leer_todas'=> notifLeerTodas($pdo),

    // Cupones
    'cupones_list'    => cuponesList($pdo),
    'cupon_save'      => cuponSave($pdo),
    'cupon_delete'    => cuponDelete($pdo),
    'cupon_validar'   => cuponValidar($pdo),

    // Marketing
    'newsletter_list'              => newsletterList($pdo),
    'newsletter_suscriptor_toggle' => newsletterSuscriptorToggle($pdo),
    'newsletter_suscriptor_delete' => newsletterSuscriptorDelete($pdo),
    'newsletter_enviar_campana'    => newsletterEnviarCampana($pdo),
    'carrito_enviar_recordatorio'  => carritoEnviarRecordatorio($pdo),
    'blacklist_list'               => blacklistList($pdo),
    'blacklist_delete'             => blacklistDelete($pdo),

    // Blog
    'blog_list'       => blogList($pdo),
    'blog_save'       => blogSave($pdo),
    'blog_delete'     => blogDelete($pdo),

    // Reviews
    'reviews_list'    => reviewsList($pdo),
    'review_aprobar'  => reviewAprobar($pdo),
    'review_delete'   => reviewDelete($pdo),

    // Analytics
    'analytics'       => analyticsData($pdo),
    'carritos_abandonados' => carritosAbandonados($pdo),

    // Configuraciones
    'config_get'      => configGet($pdo),
    'config_save'     => configSave($pdo),

    // Textos
    'textos_get'      => textosGet($pdo),
    'texto_save'      => textoSave($pdo),

    // Campañas
    'campanas_list'   => campanasList($pdo),
    'campana_save'    => campanaSave($pdo),
    'campana_delete'  => campanaDelete($pdo),
    'campana_toggle'  => campanaToggle($pdo),

    // Categorías
    'categorias_list'            => adminCategoriasList($pdo),
    'categoria_save'             => guardarCategoria($pdo),
    'categoria_delete'           => eliminarCategoria($pdo),
    'categoria_imagen_upload'    => subirImagenCategoria($pdo),

    // Comunidad
    'comunidad_list'             => comunidadList($pdo),
    'comunidad_save'             => comunidadSave($pdo),
    'comunidad_delete'           => comunidadDelete($pdo),
    'comunidad_imagen_upload'    => comunidadImagenUpload($pdo),
    
    default           => jsonResponse(['error' => 'Accion no despachada'], 500)
};
