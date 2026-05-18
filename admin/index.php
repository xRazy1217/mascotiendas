<?php
session_name('mascotiendas');
ini_set('session.cookie_path', '/');
session_start();
if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Mascotiendas</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = { theme: { extend: { colors: { 'mt-brown':'#7F5234','mt-orange':'#F7941D','mt-cream':'#F9F1E7' } } } }
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

      <button @click="seccion='marketing';cargarNewsletter();cargarCupones()"
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
    </nav>

    <div class="p-4 border-t border-white/10 space-y-2">
      <a href="/" target="_blank"
         class="block text-center text-xs font-bold text-white/40 hover:text-white transition-colors">
        <i class="fas fa-external-link-alt mr-1"></i> Ver tienda
      </a>
    </div>
  </aside>

  <!-- Content -->
  <main class="flex-grow p-6 overflow-auto">
    <?php include 'views/dashboard.php'; ?>
    <?php include 'views/productos.php'; ?>
    <?php include 'views/pedidos.php'; ?>
    <?php include 'views/usuarios.php'; ?>
    <?php include 'views/cupones.php'; ?>
    <?php include 'views/marketing.php'; ?>
    <?php include 'views/blog.php'; ?>
    <?php include 'views/reviews.php'; ?>
    <?php include 'views/notificaciones.php'; ?>
    <?php include 'views/textos.php'; ?>
    <?php include 'views/campanas.php'; ?>
  </main>
</div>

<div x-show="toast" x-cloak x-transition
     class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-mt-brown text-white px-6 py-3 rounded-2xl shadow-2xl font-bold text-sm z-50"
     x-text="toast"></div>

<script src="/assets/admin.js"></script>
</body>
</html>
