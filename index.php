<?php
require_once 'includes/funciones.php';

// ─── SITEMAP: redirigir al generador unificado ─────────────────
if (isset($_GET['sitemap'])) {
    require_once 'api/contenido.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
<?php
$pdo = getPDO();
$base_url  = 'https://mascotiendas.cl';
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path = rtrim($base_path, '/');
$p = $_GET['p'] ?? 'home';

// Determinar indexación según el tipo de página
$noindex_pages = ['login', 'registro', 'carrito', 'checkout', 'perfil', 'favoritos'];
$meta_robots = in_array($p, $noindex_pages) ? 'noindex, nofollow' : 'index, follow';

// ─── DEFAULTS META ─────────────────────────────────────────────
$meta_title = "Mascotiendas | Tienda de mascotas La Serena & Coquimbo";
$meta_desc  = "Tienda de mascotas en La Serena y Coquimbo. Comida para perros y gatos, farmacia veterinaria, arena sanitaria y snacks premium. Delivery gratis.";
$meta_keys  = "tienda mascotas La Serena, comida perros Coquimbo, farmacia veterinaria, delivery mascotas";
$og_image   = "$base_url/assets/og-image.jpg";
$og_type    = "website";
$canonical_path = '/';
$schemas    = [];
$breadcrumbs = [['name' => 'Inicio', 'url' => $base_url]];
$seo_prerender = ''; // Contenido pre-renderizado para crawlers

// ─── Cargar configuraciones de Sitio y Diseño ───────────────────
$config_stmt = $pdo->query("SELECT clave, valor FROM configuraciones WHERE clave IN ('gtm_id','ga4_id','gads_conversion_id','gads_conversion_label','theme_color_primary','theme_color_secondary','theme_color_cream','admin_whatsapp_number','bot_whatsapp_number')");
$site_config = [];
foreach ($config_stmt->fetchAll() as $r) $site_config[$r['clave']] = $r['valor'];
$gtm_id     = $site_config['gtm_id'] ?? '';
$ga4_id     = $site_config['ga4_id'] ?? '';
$theme_primary   = $site_config['theme_color_primary'] ?? '#7F5234';
$theme_secondary = $site_config['theme_color_secondary'] ?? '#F7941D';
$theme_cream     = $site_config['theme_color_cream'] ?? '#F9F1E7';

// ─── Helper: Generar slug ──────────────────────────────────────
function slugify(string $text): string {
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

// ─── SEO POR TIPO DE PÁGINA ────────────────────────────────────
if ($p === 'producto' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT p.* FROM productos p WHERE p.id = ? AND p.activo = 1");
    $stmt->execute([$_GET['id']]);
    if ($prod = $stmt->fetch()) {
        $prod_slug = $prod['slug'] ?: slugify($prod['nombre']);
        $meta_title = $prod['meta_titulo'] ?: ($prod['nombre'] . " | Mascotiendas");
        $meta_desc  = $prod['meta_descripcion'] ?: (substr(strip_tags($prod['descripcion_corta'] ?? ''), 0, 160) ?: $meta_desc);
        $meta_keys  = $prod['meta_keywords'] ?: $meta_keys;
        $canonical_path = "/producto/{$prod['id']}-{$prod_slug}";

        // Imagen principal
        $stmt_img = $pdo->prepare("SELECT url FROM producto_imagenes WHERE producto_id = ? ORDER BY posicion ASC LIMIT 1");
        $stmt_img->execute([$prod['id']]);
        if ($img = $stmt_img->fetch()) {
            $og_image = $img['url'];
            if (!str_starts_with($og_image, 'http')) $og_image = $base_url . $og_image;
        }

        // Todas las imágenes para schema
        $all_imgs_stmt = $pdo->prepare("SELECT url FROM producto_imagenes WHERE producto_id = ? ORDER BY posicion ASC");
        $all_imgs_stmt->execute([$prod['id']]);
        $all_images = array_map(function($r) use ($base_url) {
            return str_starts_with($r['url'], 'http') ? $r['url'] : $base_url . $r['url'];
        }, $all_imgs_stmt->fetchAll());

        // Reviews para AggregateRating
        $rev_stmt = $pdo->prepare("SELECT COUNT(*) as total, AVG(estrellas) as promedio FROM reviews WHERE producto_id = ? AND aprobado = 1");
        $rev_stmt->execute([$prod['id']]);
        $rev_data = $rev_stmt->fetch();

        // Categorías
        $cats_stmt = $pdo->prepare("SELECT c.nombre, c.slug FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = ?");
        $cats_stmt->execute([$prod['id']]);
        $prod_cats = $cats_stmt->fetchAll();

        $og_type = "product";

        // Schema Product enriquecido
        $product_schema = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $prod['nombre'],
            "image" => count($all_images) > 1 ? $all_images : ($all_images[0] ?? $og_image),
            "description" => strip_tags($prod['descripcion_corta'] ?? $prod['nombre']),
            "sku" => $prod['sku'] ?? "MT-" . $prod['id'],
            "brand" => ["@type" => "Brand", "name" => $prod['marca_nombre'] ?? "Mascotiendas"],
            "category" => !empty($prod_cats) ? $prod_cats[0]['nombre'] : "Mascotas",
            "offers" => [
                "@type" => "Offer",
                "url" => $base_url . $canonical_path,
                "priceCurrency" => "CLP",
                "price" => $prod['precio_rebajado'] ?: $prod['precio_normal'],
                "availability" => (($prod['inventario_actual'] ?? 0) > 0 || ($prod['en_stock'] ?? 0) == 1) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
                "itemCondition" => "https://schema.org/NewCondition",
                "seller" => ["@type" => "Organization", "name" => "Mascotiendas"]
            ]
        ];

        // AggregateRating si hay reviews
        if ($rev_data && $rev_data['total'] > 0) {
            $product_schema['aggregateRating'] = [
                "@type" => "AggregateRating",
                "ratingValue" => round($rev_data['promedio'], 1),
                "reviewCount" => (int)$rev_data['total'],
                "bestRating" => 5,
                "worstRating" => 1
            ];
        }

        $schemas[] = $product_schema;

        // Breadcrumbs
        $breadcrumbs[] = ['name' => 'Tienda', 'url' => "$base_url/tienda"];
        if (!empty($prod_cats)) {
            $breadcrumbs[] = ['name' => $prod_cats[0]['nombre'], 'url' => "$base_url/tienda?categoria=" . $prod_cats[0]['slug']];
        }
        $breadcrumbs[] = ['name' => $prod['nombre'], 'url' => $base_url . $canonical_path];

        // Pre-renderizado SEO
        $seo_price = '$' . number_format($prod['precio_rebajado'] ?: $prod['precio_normal'], 0, ',', '.');
        $seo_prerender = '<div id="seo-content" style="display:none" aria-hidden="true">'
            . '<h1>' . htmlspecialchars($prod['nombre']) . '</h1>'
            . '<p>' . htmlspecialchars(strip_tags($prod['descripcion_corta'] ?? '')) . '</p>'
            . '<span>Precio: ' . $seo_price . '</span>'
            . '<span>Marca: ' . htmlspecialchars($prod['marca_nombre'] ?? 'Mascotiendas') . '</span>';
        foreach ($prod_cats as $c) {
            $seo_prerender .= '<a href="' . $base_url . '/tienda?categoria=' . htmlspecialchars($c['slug']) . '">' . htmlspecialchars($c['nombre']) . '</a>';
        }
        $seo_prerender .= '</div>';
    }

} elseif ($p === 'blog' && isset($_GET['slug'])) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND publicado = 1");
    $stmt->execute([$_GET['slug']]);
    if ($post = $stmt->fetch()) {
        $meta_title = $post['meta_titulo'] ?: ($post['titulo'] . " | Blog Mascotiendas");
        $meta_desc  = $post['meta_descripcion'] ?: (substr(strip_tags($post['extracto'] ?? ''), 0, 160) ?: $meta_desc);
        $og_image   = $post['imagen_portada'] ?: $og_image;
        if (!str_starts_with($og_image, 'http')) $og_image = $base_url . $og_image;
        $og_type    = "article";
        $canonical_path = "/blog/" . $post['slug'];

        $schemas[] = [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $post['titulo'],
            "image" => $og_image,
            "datePublished" => $post['creado_en'],
            "dateModified" => $post['actualizado_en'] ?? $post['creado_en'],
            "author" => ["@type" => "Organization", "name" => "Mascotiendas"],
            "publisher" => [
                "@type" => "Organization",
                "name" => "Mascotiendas",
                "logo" => ["@type" => "ImageObject", "url" => "$base_url/assets/logo.webp"]
            ],
            "mainEntityOfPage" => ["@type" => "WebPage", "@id" => $base_url . $canonical_path]
        ];

        $breadcrumbs[] = ['name' => 'Blog', 'url' => "$base_url/blog"];
        $breadcrumbs[] = ['name' => $post['titulo'], 'url' => $base_url . $canonical_path];

        // Pre-renderizado SEO para blog
        $seo_prerender = '<div id="seo-content" style="display:none" aria-hidden="true">'
            . '<h1>' . htmlspecialchars($post['titulo']) . '</h1>'
            . '<p>' . htmlspecialchars(strip_tags($post['extracto'] ?? '')) . '</p>'
            . '<time datetime="' . htmlspecialchars($post['creado_en']) . '">' . date('d/m/Y', strtotime($post['creado_en'])) . '</time>'
            . '</div>';
    }

} elseif ($p === 'tienda') {
    $meta_title = "Tienda de Mascotas Online | Comida Perros y Gatos | Mascotiendas";
    $meta_desc  = "Catálogo completo de comida para perros y gatos, snacks, arena sanitaria y accesorios. Delivery gratis en La Serena y Coquimbo.";
    $meta_keys  = "comida perros La Serena, comida gatos Coquimbo, tienda mascotas online, delivery mascotas";
    $canonical_path = "/tienda";

    // Si hay categoría
    if (isset($_GET['categoria'])) {
        $cat_id = (int)$_GET['categoria'];
        $cat_stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
        $cat_stmt->execute([$cat_id]);
        if ($cat_data = $cat_stmt->fetch()) {
            $meta_title = $cat_data['meta_titulo'] ?: ("Comprar " . $cat_data['nombre'] . " | Mascotiendas La Serena");
            $meta_desc  = $cat_data['meta_descripcion'] ?: ("Encuentra " . strtolower($cat_data['nombre']) . " al mejor precio con delivery gratis en La Serena y Coquimbo. Mascotiendas, tu tienda de mascotas de confianza.");
            $canonical_path = "/tienda/categoria/{$cat_id}-" . slugify($cat_data['nombre']);
            $breadcrumbs[] = ['name' => 'Tienda', 'url' => "$base_url/tienda"];
            $breadcrumbs[] = ['name' => $cat_data['nombre'], 'url' => $base_url . $canonical_path];

            // ItemList Schema para páginas de categoría
            $il_stmt = $pdo->prepare("
                SELECT p.id, p.nombre, p.slug, p.precio_normal, p.precio_rebajado, p.en_stock,
                       (SELECT url FROM producto_imagenes pi WHERE pi.producto_id = p.id ORDER BY pi.posicion ASC LIMIT 1) AS imagen
                FROM productos p
                INNER JOIN producto_categorias pc ON pc.producto_id = p.id
                WHERE pc.categoria_id = ? AND p.activo = 1
                ORDER BY p.precio_normal ASC
                LIMIT 20
            ");
            $il_stmt->execute([$cat_id]);
            $il_prods = $il_stmt->fetchAll();
            if ($il_prods) {
                $il_items = [];
                foreach ($il_prods as $i => $pr) {
                    $pr_slug = $pr['slug'] ?: slugify($pr['nombre']);
                    $pr_url  = "$base_url/producto/{$pr['id']}-{$pr_slug}";
                    $pr_img  = $pr['imagen']
                        ? (str_starts_with($pr['imagen'], 'http') ? $pr['imagen'] : $base_url . $pr['imagen'])
                        : "$base_url/assets/no-image.png";
                    $price   = $pr['precio_rebajado'] > 0 ? $pr['precio_rebajado'] : $pr['precio_normal'];
                    $avail   = $pr['en_stock'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
                    $item_node = [
                        '@type'  => 'Product',
                        'name'   => $pr['nombre'],
                        'url'    => $pr_url,
                        'image'  => $pr_img,
                    ];
                    if ($price > 0) {
                        $item_node['offers'] = [
                            '@type'         => 'Offer',
                            'price'         => (string)(int)$price,
                            'priceCurrency' => 'CLP',
                            'availability'  => $avail,
                            'seller'        => ['@type' => 'Organization', 'name' => 'Mascotiendas'],
                        ];
                    }
                    $il_items[] = [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'item'     => $item_node,
                    ];
                }
                $schemas[] = [
                    '@context'       => 'https://schema.org',
                    '@type'          => 'ItemList',
                    'name'           => $cat_data['nombre'],
                    'description'    => $meta_desc,
                    'url'            => $base_url . $canonical_path,
                    'numberOfItems'  => count($il_items),
                    'itemListElement'=> $il_items,
                ];
            }
        }
    } else {
        $breadcrumbs[] = ['name' => 'Tienda', 'url' => "$base_url/tienda"];
    }

} elseif ($p === 'farmacia') {
    $meta_title = "Farmacia Veterinaria | Medicamentos para Mascotas | Mascotiendas";
    $meta_desc  = "Farmacia veterinaria en La Serena y Coquimbo. Antiparasitarios, vitaminas, medicamentos y productos de salud para perros y gatos con delivery gratis.";
    $meta_keys  = "farmacia veterinaria La Serena, antiparasitarios perros, vitaminas gatos, medicamentos mascotas";
    $canonical_path = "/farmacia";
    $breadcrumbs[] = ['name' => 'Farmacia Veterinaria', 'url' => "$base_url/farmacia"];
    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type'    => 'FAQPage',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => '¿Necesito receta médica para comprar antiparasitarios en Mascotiendas?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. La mayoría de los antiparasitarios (pipetas, tabletas como Simparica, collares y sprays) se venden sin receta. Solo algunos medicamentos de uso exclusivo veterinario requieren prescripción. Si tienes dudas, escríbenos por WhatsApp.'],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Qué marcas de antiparasitarios tienen disponibles?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Contamos con Simparica, Simparica Trio, Frontline, Advantage, NexGard, Drontal, Milbemax y collar Seresto, entre otras marcas reconocidas por veterinarios en Chile.'],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Con qué frecuencia debo desparasitar a mi perro?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Los cachorros se desparasitan cada 15 días hasta los 3 meses y luego mensual. Los perros adultos cada 3 meses como mínimo, o mensual si tienen acceso a exteriores o contacto con otros animales.'],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Hacen delivery de medicamentos para mascotas en La Serena y Coquimbo?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Sí. Hacemos delivery gratis en toda La Serena y Coquimbo para pedidos en nuestra farmacia veterinaria online. Los pedidos se despachan en el mismo día o al día siguiente hábil.'],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuánto cuestan los antiparasitarios para perros en Mascotiendas?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Los precios varían según el tipo y el peso del perro. Las pipetas mensuales van desde $8.000 CLP aproximadamente. Puedes revisar los precios actualizados en nuestra sección de farmacia o escribirnos al WhatsApp +569 5379 3135.'],
            ],
        ],
    ];

} elseif ($p === 'blog' && !isset($_GET['slug'])) {
    $meta_title = "Blog de Mascotas | Consejos y Cuidados | Mascotiendas";
    $meta_desc  = "Artículos y consejos sobre cuidado de mascotas, nutrición canina y felina, salud veterinaria. Blog de Mascotiendas La Serena.";
    $canonical_path = "/blog";
    $breadcrumbs[] = ['name' => 'Blog', 'url' => "$base_url/blog"];

} elseif ($p === 'comunidad') {
    $meta_title = "Comunidad Mascotiendas | La Serena & Coquimbo";
    $meta_desc  = "Únete a la comunidad de amantes de las mascotas en La Serena y Coquimbo. Eventos, actividades y más en Mascotiendas.";
    $canonical_path = "/comunidad";
    $breadcrumbs[] = ['name' => 'Comunidad', 'url' => "$base_url/comunidad"];

} else {
    $canonical_path = '/';
}

// ─── CANONICAL URL ─────────────────────────────────────────────
$canonical = $base_url . $canonical_path;

// ─── SCHEMA: Organization / PetStore (siempre presente) ────────
$schemas[] = [
    "@context" => "https://schema.org",
    "@type" => "PetStore",
    "name" => "Mascotiendas",
    "url" => $base_url,
    "logo" => "$base_url/assets/logo.webp",
    "telephone" => "+56953793135",
    "email" => "ventas@mascotiendas.cl",
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => "Av. Balmaceda 4521 Local 2",
        "addressLocality" => "La Serena",
        "addressRegion" => "Coquimbo",
        "addressCountry" => "CL"
    ],
    "openingHours" => "Mo-Su 09:00-19:00",
    "priceRange" => "$$",
    "areaServed" => [
        ["@type" => "City", "name" => "La Serena"],
        ["@type" => "City", "name" => "Coquimbo"]
    ],
    "sameAs" => []
];

// ─── SCHEMA: WebSite con SearchAction ──────────────────────────
$schemas[] = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "Mascotiendas",
    "url" => $base_url,
    "potentialAction" => [
        "@type" => "SearchAction",
        "target" => [
            "@type" => "EntryPoint",
            "urlTemplate" => "$base_url/tienda?q={search_term_string}"
        ],
        "query-input" => "required name=search_term_string"
    ]
];

// ─── SCHEMA: BreadcrumbList ────────────────────────────────────
if (count($breadcrumbs) > 1) {
    $bc_items = [];
    foreach ($breadcrumbs as $i => $bc) {
        $bc_items[] = [
            "@type" => "ListItem",
            "position" => $i + 1,
            "name" => $bc['name'],
            "item" => $bc['url']
        ];
    }
    $schemas[] = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $bc_items
    ];
}
?>
<!-- SEO Meta Tags -->
<title><?= htmlspecialchars($meta_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="keywords" content="<?= htmlspecialchars($meta_keys) ?>">
<meta name="robots" content="<?= $meta_robots ?>">
<meta name="author" content="Mascotiendas">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
<meta property="og:title" content="<?= htmlspecialchars($meta_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name" content="Mascotiendas">
<meta property="og:locale" content="es_CL">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($meta_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

<!-- Favicon & Theme -->
<meta name="theme-color" content="<?= htmlspecialchars($theme_primary) ?>">
<link rel="icon" type="image/png" href="<?= $base_path ?>/assets/favicon.png">
<link rel="apple-touch-icon" href="<?= $base_path ?>/assets/apple-touch-icon.png">

<!-- Schema.org JSON-LD -->
<?php foreach ($schemas as $schema): ?>
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endforeach; ?>

<!-- Google Tag Manager (configurable desde admin) -->
<?php if ($gtm_id): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= htmlspecialchars($gtm_id) ?>');</script>
<?php elseif ($ga4_id): ?>
<!-- Google Analytics 4 (fallback sin GTM) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga4_id) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga4_id) ?>');</script>
<?php endif; ?>

<!-- DataLayer base -->
<script>window.dataLayer = window.dataLayer || [];</script>

<script>
  window.MT_BASE_PATH = "<?= htmlspecialchars($base_path) ?>";
  window.MT_SEO_CONFIG = <?= json_encode($site_config, JSON_UNESCAPED_UNICODE) ?>;
  window.MT_CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?? '' ?>";
  (function() {
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
      if (typeof url === 'string' && url.startsWith('/api/')) {
        url = (window.MT_BASE_PATH || '') + url;
      }

      // Auto-inyectar token CSRF para peticiones de escritura
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
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/app.min.css?v=<?= filemtime(__DIR__ . '/assets/css/app.min.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --color-primary: <?= $theme_primary ?>;
  --color-primary-light: <?= adjustBrightness($theme_primary, 15) ?>;
  --color-secondary: <?= $theme_secondary ?>;
  --color-cream: <?= $theme_cream ?>;
}
[x-cloak]{display:none!important}
.fade-in{animation:fadeIn .4s ease-out}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.float-anim{animation:float 4s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}
.desc-content { color: #475569; font-size: 0.875rem; line-height: 1.8; }
.desc-content h1,.desc-content h2,.desc-content h3 { font-size: 1rem; font-weight: 900; color: var(--color-primary); margin: 1.2rem 0 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
.desc-content p { margin-bottom: 0.75rem; }
.desc-content ul,.desc-content ol { padding-left: 1.25rem; margin-bottom: 0.75rem; }
.desc-content ul { list-style-type: disc; }
.desc-content ol { list-style-type: decimal; }
.desc-content li { margin-bottom: 0.35rem; }
.desc-content strong { font-weight: 800; color: var(--color-primary); }

/* ─── SCROLLBAR ESTILO PREMIUM ─── */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
::-webkit-scrollbar-track {
  background: var(--color-cream);
  border-radius: 10px;
}
::-webkit-scrollbar-thumb {
  background: var(--color-primary);
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: var(--color-primary-light);
}

/* ─── EFECTOS DE TARJETA PREMIUM (ZOOM & SHADOW) ─── */
.premium-card {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.premium-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.premium-card-img {
  transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.premium-card:hover .premium-card-img {
  transform: scale(1.08) !important;
}

/* ─── BOTÓN ELEVADO CON INTERACCIÓN ─── */
.premium-btn {
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.premium-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
.premium-btn:active {
  transform: translateY(0);
}

/* ─── EFECTO GLASSMORPHISM PARCIAL ─── */
.glass-effect {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.4);
}
</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800" x-data="app()" x-init="init()">

<?php if ($gtm_id): ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= htmlspecialchars($gtm_id) ?>" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
<?php endif; ?>

<?php include 'includes/header.php'; ?>

<!-- Banner delivery gratis -->
<div class="bg-mt-orange text-white text-center py-2 text-xs font-black uppercase tracking-widest">
  <i class="fas fa-truck mr-2"></i>Delivery gratis en La Serena y Coquimbo
  <span class="mx-3 opacity-50">|</span>
  <i class="fab fa-whatsapp mr-1"></i>
  <a :href="'https://wa.me/' + (config.bot_whatsapp_number || '56953793135')" target="_blank" class="hover:underline" x-text="'+' + (config.bot_whatsapp_number || '56953793135')"></a>
</div>

<!-- SEO Pre-renderizado (contenido visible para crawlers) -->
<?= $seo_prerender ?>

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

<script type="module" src="<?= $base_path ?>/assets/app.js?v=<?= filemtime(__DIR__ . '/assets/app.js') ?>"></script>
<script type="module" src="<?= $base_path ?>/assets/vendor/alpine.js"></script>
</body>
</html>
