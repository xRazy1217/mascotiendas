<?php
session_name('mascotiendas');
ini_set('session.cookie_path', '/');
session_start();

// Sitemap
if (isset($_GET['sitemap']) || isset($_GET['sitemap'])) {
    require_once 'config/db.php';
    $pdo = getPDO();
    $base = 'https://mascotiendas.cl';
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach (['','?p=tienda','?p=farmacia','?p=comunidad','?p=blog'] as $url) {
        echo "<url><loc>$base/$url</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>";
    }
    foreach ($pdo->query("SELECT id, actualizado_en FROM productos WHERE activo=1")->fetchAll() as $p) {
        echo "<url><loc>$base/?p=producto&amp;id={$p['id']}</loc><lastmod>".date('Y-m-d',strtotime($p['actualizado_en']))."</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>";
    }
    foreach ($pdo->query("SELECT slug, actualizado_en FROM blog_posts WHERE publicado=1")->fetchAll() as $p) {
        echo "<url><loc>$base/?p=blog&amp;slug={$p['slug']}</loc><lastmod>".date('Y-m-d',strtotime($p['actualizado_en']))."</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>";
    }
    echo '</urlset>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mascotiendas | Tienda de mascotas La Serena &amp; Coquimbo</title>
<meta name="description" content="Tienda de mascotas en La Serena y Coquimbo. Comida para perros y gatos, farmacia veterinaria, arena sanitaria y snacks premium. Delivery gratis zona urbana.">
<meta name="keywords" content="tienda mascotas La Serena, comida perros Coquimbo, farmacia veterinaria, delivery mascotas">
<meta property="og:title" content="Mascotiendas | La Serena &amp; Coquimbo">
<meta property="og:description" content="Comida para perros y gatos, farmacia veterinaria y más. Delivery gratis en La Serena y Coquimbo.">
<meta property="og:image" content="https://mascotiendas.cl/assets/og-image.jpg">
<meta property="og:type" content="website">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://mascotiendas.cl/">
<!-- Schema.org Organization -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "PetStore",
  "name": "Mascotiendas",
  "url": "https://mascotiendas.cl",
  "telephone": "+56953793135",
  "email": "ventas@mascotiendas.cl",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Av. Balmaceda 4521 Local 2",
    "addressLocality": "La Serena",
    "addressRegion": "Coquimbo",
    "addressCountry": "CL"
  },
  "openingHours": "Mo-Su 09:00-19:00",
  "priceRange": "$$"
}
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  theme: { extend: { colors: {
    'mt-brown': '#7F5234',
    'mt-orange': '#F7941D',
    'mt-cream': '#F9F1E7'
  }}}
}
</script>
<style>
[x-cloak]{display:none!important}
.fade-in{animation:fadeIn .4s ease-out}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.float-anim{animation:float 4s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}
.desc-content { color: #475569; font-size: 0.875rem; line-height: 1.8; }
.desc-content h1,.desc-content h2,.desc-content h3 { font-size: 1rem; font-weight: 900; color: #7F5234; margin: 1.2rem 0 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
.desc-content p { margin-bottom: 0.75rem; }
.desc-content ul,.desc-content ol { padding-left: 1.25rem; margin-bottom: 0.75rem; }
.desc-content ul { list-style-type: disc; }
.desc-content ol { list-style-type: decimal; }
.desc-content li { margin-bottom: 0.35rem; }
.desc-content strong { font-weight: 800; color: #7F5234; }
</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800" x-data="app()" x-init="init()">

<?php include 'includes/header.php'; ?>

<!-- Banner delivery gratis -->
<div class="bg-mt-orange text-white text-center py-2 text-xs font-black uppercase tracking-widest">
  <i class="fas fa-truck mr-2"></i>Delivery gratis en La Serena y Coquimbo zona urbana
  <span class="mx-3 opacity-50">|</span>
  <i class="fab fa-whatsapp mr-1"></i>
  <a href="https://wa.me/56953793135" target="_blank" class="hover:underline">+569 5379 3135</a>
</div>

<main class="container mx-auto px-4 py-6 min-h-screen">
  <?php include 'views/home.php'; ?>
  <?php include 'views/tienda.php'; ?>
  <?php include 'views/farmacia.php'; ?>
  <?php include 'views/producto.php'; ?>
  <?php include 'views/comunidad.php'; ?>
  <?php include 'views/carrito.php'; ?>
  <?php include 'views/checkout.php'; ?>
  <?php include 'views/login.php'; ?>
  <?php include 'views/registro.php'; ?>
  <?php include 'views/perfil.php'; ?>
  <?php include 'views/favoritos.php'; ?>
  <?php include 'views/blog.php'; ?>
  <?php include 'views/pagina.php'; ?>
</main>

<?php include 'includes/toast.php'; ?>
<?php include 'includes/popup.php'; ?>
<?php include 'includes/whatsapp.php'; ?>
<?php include 'includes/footer.php'; ?>

<script src="/assets/app.js"></script>
</body>
</html>
