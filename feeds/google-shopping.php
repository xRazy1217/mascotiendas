<?php
/**
 * Google Shopping Feed – Mascotiendas
 * 
 * Genera un feed RSS 2.0 compatible con Google Merchant Center.
 * Incluye todos los productos activos con imágenes, categorías, precios y envío.
 * 
 * @see https://support.google.com/merchants/answer/7052112
 */

// ─── Conexión a base de datos ───────────────────────────────────────────────
require_once __DIR__ . '/../config/db.php';
$pdo = getPDO();

// ─── Constantes ─────────────────────────────────────────────────────────────
define('BASE_URL', 'https://mascotiendas.cl');

// ─── Mapeo de categorías a taxonomía Google ─────────────────────────────────
$googleCategoryMap = [
    'comida-perros'     => 'Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Food',
    'comida-gatos'      => 'Animals & Pet Supplies > Pet Supplies > Cat Supplies > Cat Food',
    'arena-gatos'       => 'Animals & Pet Supplies > Pet Supplies > Cat Supplies > Cat Litter',
    'arena-sanitaria'   => 'Animals & Pet Supplies > Pet Supplies > Cat Supplies > Cat Litter',
    'farmacia-mascotas' => 'Animals & Pet Supplies > Pet Supplies > Pet Health',
];
$defaultGoogleCategory = 'Animals & Pet Supplies > Pet Supplies';

// ─── Función auxiliar: slugify ──────────────────────────────────────────────
/**
 * Convierte una cadena de texto en un slug URL-friendly.
 *
 * @param  string $text Texto a convertir
 * @return string       Slug resultante
 */
function slugify(string $text): string
{
    // Reemplazar caracteres acentuados y especiales
    $text = transliterator_transliterate(
        'Any-Latin; Latin-ASCII; Lower()',
        $text
    );

    // Si transliterator no está disponible, respaldo manual
    if ($text === false) {
        $text = mb_strtolower($text, 'UTF-8');
        $search  = ['á','é','í','ó','ú','ñ','ü'];
        $replace = ['a','e','i','o','u','n','u'];
        $text = str_replace($search, $replace, $text);
    }

    // Reemplazar todo lo que no sea alfanumérico por guiones
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    // Eliminar guiones al inicio y final
    $text = trim($text, '-');
    // Colapsar guiones múltiples
    $text = preg_replace('/-+/', '-', $text);

    return $text;
}

// ─── Función auxiliar: escapar texto XML ────────────────────────────────────
/**
 * Envuelve el contenido en CDATA para evitar problemas con caracteres especiales.
 *
 * @param  string $text Texto a envolver
 * @return string       Texto envuelto en CDATA
 */
function cdataWrap(string $text): string
{
    // Reemplazar cualquier ]]> dentro del texto para evitar romper el CDATA
    $text = str_replace(']]>', ']]]]><![CDATA[>', $text);
    return '<![CDATA[' . $text . ']]>';
}

/**
 * Escapa texto para uso seguro dentro de atributos/valores XML.
 *
 * @param  string $text Texto a escapar
 * @return string       Texto escapado
 */
function xmlEscape(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// ─── Consulta principal de productos ────────────────────────────────────────
$sql = "
    SELECT
        p.id,
        p.nombre,
        p.slug,
        p.descripcion_corta,
        p.precio_normal,
        p.precio_rebajado,
        p.en_stock,
        pi_img.url AS imagen_url,
        GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR '||') AS categorias_nombres,
        GROUP_CONCAT(DISTINCT c.slug ORDER BY c.nombre SEPARATOR '||') AS categorias_slugs
    FROM productos p
    LEFT JOIN producto_imagenes pi_img
        ON pi_img.producto_id = p.id
        AND pi_img.posicion = 0
    LEFT JOIN producto_categorias pc
        ON pc.producto_id = p.id
    LEFT JOIN categorias c
        ON c.id = pc.categoria_id
    WHERE p.activo = 1
    GROUP BY p.id
    ORDER BY p.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Cabeceras HTTP ─────────────────────────────────────────────────────────
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
header('X-Robots-Tag: noindex');

// ─── Generar XML ────────────────────────────────────────────────────────────
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
    <title>Mascotiendas – Google Shopping Feed</title>
    <link><?= BASE_URL ?></link>
    <description>Productos para mascotas – Mascotiendas.cl</description>
<?php foreach ($productos as $producto): ?>
<?php
    // ── Slug del producto ───────────────────────────────────────────────
    $slug = !empty($producto['slug'])
        ? $producto['slug']
        : slugify($producto['nombre']);

    // ── URL canónica del producto ───────────────────────────────────────
    $link = BASE_URL . '/producto/' . $producto['id'] . '-' . $slug;

    // ── Título (máximo 150 caracteres) ──────────────────────────────────
    $title = mb_substr($producto['nombre'], 0, 150, 'UTF-8');

    // ── Descripción (máximo 5000 caracteres, sin HTML) ──────────────────
    $description = !empty($producto['descripcion_corta'])
        ? mb_substr(strip_tags($producto['descripcion_corta']), 0, 5000, 'UTF-8')
        : $producto['nombre'];

    // ── Imagen ──────────────────────────────────────────────────────────
    $imageUrl = '';
    if (!empty($producto['imagen_url'])) {
        $imageUrl = $producto['imagen_url'];
        // Si la URL es relativa, anteponer la URL base
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = BASE_URL . '/' . ltrim($imageUrl, '/');
        }
    }

    // ── Precios ─────────────────────────────────────────────────────────
    $hasRebajado = !empty($producto['precio_rebajado']) && $producto['precio_rebajado'] > 0;
    if ($hasRebajado) {
        $price     = number_format($producto['precio_normal'], 0, '', '');
        $salePrice = number_format($producto['precio_rebajado'], 0, '', '');
    } else {
        $price     = number_format($producto['precio_normal'], 0, '', '');
        $salePrice = null;
    }

    // ── Disponibilidad ──────────────────────────────────────────────────
    $availability = !empty($producto['en_stock']) ? 'in stock' : 'out of stock';

    // ── Marca ───────────────────────────────────────────────────────────
    $brand = 'Mascotiendas';
    $nombreUpper = mb_strtoupper($producto['nombre'], 'UTF-8');
    $marcasConocidas = [
        'STAY HAPPY', 'ZUPET', 'DOCKENNEDY', 'PRO PLAN', 'MONGE', 'SENSE', 
        'AGILITY', 'HILLS', 'HILL\'S', 'ROYAL CANIN', 'ACANA', 'ORIJEN', 
        'CHATI', 'CATCHOW', 'DOGCHOW', 'PEDIGREE', 'WHISKAS', 'FELIX',
        'CHURRU', 'CIAO', 'KONG', 'FURMINATOR', 'FRISKIES', 'EUKANUBA',
        'BRAVECTO', 'NEXGARD', 'SIMPARICA', 'APOQUEL', 'FRONTILNE', 'ADVANTIX'
    ];
    foreach ($marcasConocidas as $m) {
        if (str_contains($nombreUpper, $m)) {
            $brand = ucwords(strtolower($m));
            break;
        }
    }

    // ── Categorías ──────────────────────────────────────────────────────
    $catNombres = !empty($producto['categorias_nombres'])
        ? explode('||', $producto['categorias_nombres'])
        : [];
    $catSlugs = !empty($producto['categorias_slugs'])
        ? explode('||', $producto['categorias_slugs'])
        : [];

    $productType = !empty($catNombres[0]) ? $catNombres[0] : '';

    // Determinar la categoría Google basada en los slugs
    $googleCategory = $defaultGoogleCategory;
    foreach ($catSlugs as $catSlug) {
        if (isset($googleCategoryMap[$catSlug])) {
            $googleCategory = $googleCategoryMap[$catSlug];
            break;
        }
    }
?>
    <item>
        <g:id><?= (int) $producto['id'] ?></g:id>
        <g:title><?= cdataWrap($title) ?></g:title>
        <g:description><?= cdataWrap($description) ?></g:description>
        <g:link><?= xmlEscape($link) ?></g:link>
<?php if ($imageUrl): ?>
        <g:image_link><?= xmlEscape($imageUrl) ?></g:image_link>
<?php endif; ?>
        <g:price><?= $price ?> CLP</g:price>
<?php if ($salePrice !== null): ?>
        <g:sale_price><?= $salePrice ?> CLP</g:sale_price>
<?php endif; ?>
        <g:availability><?= $availability ?></g:availability>
        <g:condition>new</g:condition>
        <g:brand><?= cdataWrap($brand) ?></g:brand>
        <g:google_product_category><?= xmlEscape($googleCategory) ?></g:google_product_category>
<?php if ($productType): ?>
        <g:product_type><?= cdataWrap($productType) ?></g:product_type>
<?php endif; ?>
        <g:shipping>
            <g:country>CL</g:country>
            <g:price>0 CLP</g:price>
        </g:shipping>
    </item>
<?php endforeach; ?>
</channel>
</rss>
