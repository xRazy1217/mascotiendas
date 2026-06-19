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
    'comunidad' => getComunidad($pdo),
    default   => jsonResponse(['error' => 'Accion no valida'], 400)
};

function getComunidad(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM comunidad_contenido WHERE activo = 1 ORDER BY id DESC");
    jsonResponse($stmt->fetchAll());
}

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
    $stmt = $pdo->prepare("SELECT * FROM campanas WHERE activo=1 AND (fecha_ini IS NULL OR fecha_ini <= ?) AND (fecha_fin IS NULL OR fecha_fin >= ?) ORDER BY creado_en DESC LIMIT 1");
    $stmt->execute([$hoy, $hoy]);
    $campana = $stmt->fetch();
    jsonResponse($campana ?: null);
}

function getHorarios(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM horarios_despacho WHERE activo=1 ORDER BY dia, hora_ini");
    jsonResponse($stmt->fetchAll());
}

// ─── Helper: Generar slug SEO-friendly ─────────────────────────
function sitemapSlugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[áàäâ]/u', 'a', $text);
    $text = preg_replace('/[éèëê]/u', 'e', $text);
    $text = preg_replace('/[íìïî]/u', 'i', $text);
    $text = preg_replace('/[óòöô]/u', 'o', $text);
    $text = preg_replace('/[úùüû]/u', 'u', $text);
    $text = preg_replace('/ñ/u', 'n', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ─── SITEMAP XML UNIFICADO CON URLS AMIGABLES ──────────────────
function getSitemap(PDO $pdo): void {
    ob_end_clean();
    $base = 'https://mascotiendas.cl';
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    // ── Páginas estáticas principales ──
    $staticPages = [
        ['loc' => '/',          'changefreq' => 'daily',   'priority' => '1.0'],
        ['loc' => '/tienda',    'changefreq' => 'daily',   'priority' => '0.9'],
        ['loc' => '/farmacia',  'changefreq' => 'weekly',  'priority' => '0.8'],
        ['loc' => '/comunidad', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => '/blog',      'changefreq' => 'weekly',  'priority' => '0.7'],
    ];
    foreach ($staticPages as $sp) {
        echo "<url><loc>{$base}{$sp['loc']}</loc><changefreq>{$sp['changefreq']}</changefreq><priority>{$sp['priority']}</priority></url>";
    }

    // ── Categorías ──
    $cats = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    foreach ($cats->fetchAll() as $c) {
        $slug = sitemapSlugify($c['nombre']);
        echo "<url><loc>{$base}/tienda/categoria/{$c['id']}-{$slug}</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>";
    }

    // ── Productos (con URLs amigables e imágenes) ──
    $prods = $pdo->query("
        SELECT p.id, p.nombre, p.slug, p.actualizado_en,
               (SELECT url FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.posicion = 0 LIMIT 1) AS imagen
        FROM productos p WHERE p.activo = 1
        ORDER BY p.actualizado_en DESC
    ");
    foreach ($prods->fetchAll() as $p) {
        $slug = $p['slug'] ?: sitemapSlugify($p['nombre']);
        $fecha = date('Y-m-d', strtotime($p['actualizado_en']));
        $imgTag = '';
        if ($p['imagen']) {
            $imgUrl = str_starts_with($p['imagen'], 'http') ? htmlspecialchars($p['imagen']) : htmlspecialchars($base . $p['imagen']);
            $imgTitle = htmlspecialchars($p['nombre']);
            $imgTag = "<image:image><image:loc>{$imgUrl}</image:loc><image:title>{$imgTitle}</image:title></image:image>";
        }
        echo "<url><loc>{$base}/producto/{$p['id']}-{$slug}</loc><lastmod>{$fecha}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority>{$imgTag}</url>";
    }

    // ── Blog posts ──
    $posts = $pdo->query("SELECT slug, actualizado_en, imagen_portada, titulo FROM blog_posts WHERE publicado=1 ORDER BY creado_en DESC");
    foreach ($posts->fetchAll() as $p) {
        $fecha = date('Y-m-d', strtotime($p['actualizado_en']));
        $imgTag = '';
        if ($p['imagen_portada']) {
            $imgUrl = str_starts_with($p['imagen_portada'], 'http') ? htmlspecialchars($p['imagen_portada']) : htmlspecialchars($base . $p['imagen_portada']);
            $imgTitle = htmlspecialchars($p['titulo']);
            $imgTag = "<image:image><image:loc>{$imgUrl}</image:loc><image:title>{$imgTitle}</image:title></image:image>";
        }
        echo "<url><loc>{$base}/blog/{$p['slug']}</loc><lastmod>{$fecha}</lastmod><changefreq>monthly</changefreq><priority>0.6</priority>{$imgTag}</url>";
    }

    // ── Páginas estáticas de BD ──
    $pags = $pdo->query("SELECT slug FROM paginas WHERE activo=1");
    foreach ($pags->fetchAll() as $pg) {
        echo "<url><loc>{$base}/pagina/{$pg['slug']}</loc><changefreq>yearly</changefreq><priority>0.3</priority></url>";
    }

    echo '</urlset>';
    exit;
}
