<?php
ob_start();
require_once __DIR__ . '/../includes/funciones.php';

$pdo    = getPDO();
$action = $_GET['action'] ?? '';

match($action) {
    'lista'   => listaPosts($pdo),
    'detalle' => detallePost($pdo),
    'pagina'  => getPagina($pdo),
    'zonas'   => getZonas($pdo),
    'horarios'=> getHorarios($pdo),
    'sitemap' => getSitemap($pdo),
    'config'  => getConfig($pdo),
    'textos'  => getTextos($pdo),
    'campana_activa' => getCampanaActiva($pdo),
    default   => jsonResponse(['error' => 'Accion no valida'], 400)
};

function listaPosts(PDO $pdo): void {
    $stmt = $pdo->query("SELECT id,titulo,slug,extracto,imagen_portada,creado_en FROM blog_posts WHERE publicado=1 ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function detallePost(PDO $pdo): void {
    $slug = $_GET['slug'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug=? AND publicado=1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if (!$post) jsonResponse(['error' => 'No encontrado'], 404);
    jsonResponse($post);
}

function getPagina(PDO $pdo): void {
    $slug = $_GET['slug'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM paginas WHERE slug=? AND activo=1");
    $stmt->execute([$slug]);
    $pagina = $stmt->fetch();
    if (!$pagina) jsonResponse(['error' => 'No encontrada'], 404);
    jsonResponse($pagina);
}

function getZonas(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM zonas_delivery WHERE activo=1 ORDER BY costo ASC");
    jsonResponse($stmt->fetchAll());
}

function getConfig(PDO $pdo): void {
    $stmt = $pdo->query("SELECT clave, valor FROM configuraciones");
    $rows = $stmt->fetchAll();
    $config = [];
    foreach ($rows as $r) $config[$r['clave']] = $r['valor'];
    jsonResponse($config);
}

function getTextos(PDO $pdo): void {
    $stmt = $pdo->query("SELECT clave, valor FROM textos");
    $rows = $stmt->fetchAll();
    $textos = [];
    foreach ($rows as $r) $textos[$r['clave']] = $r['valor'];
    jsonResponse($textos);
}

function getCampanaActiva(PDO $pdo): void {
    $hoy = date('Y-m-d');
    $stmt = $pdo->query("SELECT * FROM campanas WHERE activo=1 AND (fecha_ini IS NULL OR fecha_ini <= '$hoy') AND (fecha_fin IS NULL OR fecha_fin >= '$hoy') ORDER BY creado_en DESC LIMIT 1");
    $campana = $stmt->fetch();
    jsonResponse($campana ?: null);
}

function getHorarios(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM horarios_despacho WHERE activo=1 ORDER BY dia, hora_ini");
    jsonResponse($stmt->fetchAll());
}

function getSitemap(PDO $pdo): void {
    ob_end_clean();
    $base = 'https://cristobalv6.sg-host.com';
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    // Páginas estáticas
    $estaticas = ['', '?p=tienda', '?p=farmacia', '?p=comunidad'];
    foreach ($estaticas as $url) {
        echo "<url><loc>$base/$url</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>";
    }

    // Productos
    $prods = $pdo->query("SELECT id, slug, actualizado_en FROM productos WHERE activo=1");
    foreach ($prods->fetchAll() as $p) {
        $slug = $p['slug'] ?: $p['id'];
        $fecha = date('Y-m-d', strtotime($p['actualizado_en']));
        echo "<url><loc>$base/?p=producto&amp;id={$p['id']}</loc><lastmod>$fecha</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>";
    }

    // Blog
    $posts = $pdo->query("SELECT slug, actualizado_en FROM blog_posts WHERE publicado=1");
    foreach ($posts->fetchAll() as $p) {
        $fecha = date('Y-m-d', strtotime($p['actualizado_en']));
        echo "<url><loc>$base/?p=blog&amp;slug={$p['slug']}</loc><lastmod>$fecha</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>";
    }

    // Páginas estáticas BD
    $pags = $pdo->query("SELECT slug FROM paginas WHERE activo=1");
    foreach ($pags->fetchAll() as $pg) {
        echo "<url><loc>$base/?p=pagina&amp;slug={$pg['slug']}</loc><changefreq>yearly</changefreq><priority>0.3</priority></url>";
    }

    echo '</urlset>';
    exit;
}
