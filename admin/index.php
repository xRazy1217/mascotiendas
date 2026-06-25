<?php
require_once __DIR__ . '/../includes/funciones.php';
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path = preg_replace('/\/admin\/?$/', '', $base_path);
$base_path = rtrim($base_path, '/');
if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ' . ($base_path ?: '/') . '/');
    exit;
}

$pdo = getPDO();
$config_stmt = $pdo->query("SELECT clave, valor FROM configuraciones WHERE clave IN ('theme_color_primary','theme_color_secondary','theme_color_cream')");
$theme_config = [];
foreach ($config_stmt->fetchAll() as $r) {
    $theme_config[$r['clave']] = $r['valor'];
}
$theme_primary   = $theme_config['theme_color_primary'] ?? '#7F5234';
$theme_secondary = $theme_config['theme_color_secondary'] ?? '#F7941D';
$theme_cream     = $theme_config['theme_color_cream'] ?? '#F9F1E7';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
<title>Admin — Mascotiendas</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/app.min.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/app.min.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
window.MT_BASE_PATH = "<?= htmlspecialchars($base_path) ?>";
window.MT_CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
(function() {
  const originalFetch = window.fetch;
  window.fetch = function(url, options) {
    if (typeof url === 'string' && url.startsWith('/api/')) {
      url = (window.MT_BASE_PATH || '') + url;
    }

    options = options || {};
    const method = (options.method || 'GET').toUpperCase();
    if (method !== 'GET' && window.MT_CSRF_TOKEN) {
      options.headers = options.headers || {};
      if (options.headers instanceof Headers) {
        options.headers.set('X-CSRF-Token', window.MT_CSRF_TOKEN);
      } else {
        options.headers['X-CSRF-Token'] = window.MT_CSRF_TOKEN;
      }
    }

    return originalFetch(url, options);
  };
})();
</script>
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-slate-100 font-sans" x-data="admin()" x-init="init()">
<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="w-60 bg-mt-brown text-white flex flex-col shadow-2xl flex-shrink-0">
    <div class="p-5 border-b border-white/10">
      <h1 class="text-lg font-black uppercase italic">Masco<span class="text-mt-orange">tiendas</span></h1>
      <p class="text-xs text-white/40 mt-0.5">Panel Admin</p>
    </div>

    <nav class="flex-grow p-3 space-y-1 overflow-y-auto">
      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest px-3 pt-2 pb-1">Principal</p>

      <button @click="seccion='dashboard'"
              :class="seccion==='dashboard'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-chart-bar w-4"></i> Dashboard
      </button>

      <button @click="seccion='notificaciones';cargarNotif()"
              :class="seccion==='notificaciones'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center justify-between gap-3">
        <span class="flex items-center gap-3"><i class="fas fa-bell w-4"></i> Notificaciones</span>
        <span x-show="stats.no_leidas>0" x-text="stats.no_leidas"
              class="bg-red-500 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center"></span>
      </button>

      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest px-3 pt-3 pb-1">Tienda</p>

      <button @click="seccion='productos';cargarProductos()"
              :class="seccion==='productos'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-box w-4"></i> Productos
      </button>

      <button @click="seccion='categorias';cargarCategoriasAdmin()"
              :class="seccion==='categorias'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-folder w-4"></i> Categorías
      </button>

      <button @click="seccion='pedidos';cargarPedidos()"
              :class="seccion==='pedidos'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center justify-between">
        <span class="flex items-center gap-3"><i class="fas fa-shopping-bag w-4"></i> Pedidos</span>
        <span x-show="stats.pendientes>0" x-text="stats.pendientes"
              class="bg-red-500 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center"></span>
      </button>

      <button @click="seccion='cupones';cargarCupones()"
              :class="seccion==='cupones'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-tag w-4"></i> Cupones
      </button>

      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest px-3 pt-3 pb-1">Clientes</p>

      <button @click="seccion='usuarios';cargarUsuarios()"
              :class="seccion==='usuarios'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-users w-4"></i> Usuarios
      </button>

      <button @click="seccion='reviews';cargarReviewsAdmin()"
              :class="seccion==='reviews'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-star w-4"></i> Reseñas
      </button>

      <button @click="seccion='blog';cargarBlogAdmin()"
              :class="seccion==='blog'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-newspaper w-4"></i> Blog
      </button>

      <button @click="seccion='marketing';cargarNewsletter();cargarCupones();cargarCarritos();cargarBlacklist()"
              :class="seccion==='marketing'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-bullhorn w-4"></i> Marketing
      </button>

      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest px-3 pt-3 pb-1">Contenido</p>

      <button @click="seccion='textos';cargarTextos()"
              :class="seccion==='textos'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-magic w-4"></i> Textos Mágicos
      </button>

      <button @click="seccion='campanas';cargarCampanas()"
              :class="seccion==='campanas'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-bullseye w-4"></i> Campañas
      </button>

      <button @click="seccion='comunidad';cargarComunidadAdmin()"
              :class="seccion==='comunidad'?'bg-mt-orange text-white':'text-white/70 hover:bg-white/10'"
              class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center gap-3">
        <i class="fas fa-users w-4"></i> Comunidad
      </button>
    </nav>

    <div class="p-4 border-t border-white/10 space-y-2">
      <a href="<?= $base_path ?>/" target="_blank"
         class="block text-center text-xs font-bold text-white/40 hover:text-white transition-colors">
        <i class="fas fa-external-link-alt mr-1"></i> Ver tienda
      </a>
    </div>
  </aside>

  <!-- Content -->
  <main class="flex-grow p-6 overflow-auto">
    <?php include 'views/dashboard.php'; ?>
    <?php include 'views/productos.php'; ?>
    <?php include 'views/categorias.php'; ?>
    <?php include 'views/pedidos.php'; ?>
    <?php include 'views/usuarios.php'; ?>
    <?php include 'views/cupones.php'; ?>
    <?php include 'views/marketing.php'; ?>
    <?php include 'views/blog.php'; ?>
    <?php include 'views/reviews.php'; ?>
    <?php include 'views/notificaciones.php'; ?>
    <?php include 'views/textos.php'; ?>
    <?php include 'views/campanas.php'; ?>
    <?php include 'views/comunidad.php'; ?>
  </main>
</div>

<!-- Barra de acciones masivas flotante -->
<div x-show="selectedProducts.length > 0 && seccion === 'productos'"
     x-cloak
     x-transition:enter="transition ease-out duration-300 transform"
     x-transition:enter-start="translate-y-20 opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="translate-y-20 opacity-0"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white border border-mt-cream shadow-2xl rounded-2xl px-6 py-4 flex flex-wrap items-center gap-4 z-40 max-w-[90vw] md:max-w-3xl">
  <div class="flex items-center gap-2">
    <span class="w-6 h-6 rounded-full bg-mt-orange text-white text-xs font-black flex items-center justify-center" x-text="selectedProducts.length"></span>
    <span class="text-xs font-black text-mt-brown uppercase tracking-wider">Productos seleccionados</span>
  </div>
  
  <div class="h-6 w-px bg-mt-cream hidden md:block"></div>
  
  <div class="flex flex-wrap gap-2">
    <button @click="bulkStockAction('stock_on')"
            class="px-3 py-2 bg-green-50 text-green-700 hover:bg-green-700 hover:text-white rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5">
      <i class="fas fa-check"></i>
      <span>Marcar con Stock</span>
    </button>
    <button @click="bulkStockAction('stock_off')"
            class="px-3 py-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5">
      <i class="fas fa-times"></i>
      <span>Marcar sin Stock</span>
    </button>
    <button @click="bulkStockAction('inventario_set')"
            class="px-3 py-2 bg-mt-cream text-mt-brown hover:bg-mt-orange hover:text-white rounded-xl font-bold text-xs transition-colors flex items-center gap-1.5">
      <i class="fas fa-boxes"></i>
      <span>Ajustar Inventario</span>
    </button>
    <button @click="selectedProducts = []"
            class="px-3 py-2 bg-slate-100 text-slate-500 hover:bg-slate-200 rounded-xl font-bold text-xs transition-colors">
      Cancelar
    </button>
  </div>
</div>

<div x-show="toast" x-cloak x-transition
     class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-mt-brown text-white px-6 py-3 rounded-2xl shadow-2xl font-bold text-sm z-50"
     x-text="toast"></div>

<script type="module" src="<?= $base_path ?>/assets/admin.js?v=<?= filemtime(__DIR__ . '/../assets/admin.js') ?>"></script>
<script type="module" src="<?= $base_path ?>/assets/vendor/alpine.js"></script>
</body>
</html>
