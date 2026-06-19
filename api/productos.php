<?php
require_once __DIR__ . '/../includes/funciones.php';

$pdo    = getPDO();
$action = $_GET['action'] ?? 'list';

match($action) {
    'list'       => listarProductos($pdo),
    'detalle'    => detalleProducto($pdo),
    'categorias' => listarCategorias($pdo),
    'destacados' => destacados($pdo),
    default      => jsonResponse(['error' => 'Acción no válida'], 400)
};

function listarProductos(PDO $pdo): void {
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = 12;
    $offset   = ($page - 1) * $limit;
    $cat      = $_GET['categoria'] ?? '';
    $buscar   = $_GET['q'] ?? '';
    $tipo     = $_GET['tipo'] ?? ''; // tienda | farmacia

    $where  = ['p.activo = 1'];
    $params = [];

    if ($cat) {
        $where[]  = 'EXISTS (SELECT 1 FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id AND c.slug = ?)';
        $params[] = $cat;
    }
    if ($buscar) {
        $where[]  = '(p.nombre LIKE ? OR p.descripcion_corta LIKE ?)';
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }
    if ($tipo === 'farmacia') {
        $where[]  = 'EXISTS (SELECT 1 FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id AND c.slug = ?)';
        $params[] = 'farmacia-mascotas';
    } elseif ($tipo === 'tienda') {
        $where[]  = 'NOT EXISTS (SELECT 1 FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id AND c.slug = ?)';
        $params[] = 'farmacia-mascotas';
    }

    $whereSQL = implode(' AND ', $where);

    $total = $pdo->prepare("SELECT COUNT(*) FROM productos p WHERE $whereSQL");
    $total->execute($params);
    $totalRows = (int)$total->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.id, p.nombre, p.precio_normal, p.precio_rebajado, p.en_stock, p.slug,
               (SELECT url FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.posicion = 0 LIMIT 1) AS imagen,
               (SELECT crop_config FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.posicion = 0 LIMIT 1) AS imagen_crop,
               (SELECT GROUP_CONCAT(c.nombre SEPARATOR ', ') FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = p.id) AS categorias
        FROM productos p
        WHERE $whereSQL
        ORDER BY p.id DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);

    jsonResponse([
        'productos'   => $stmt->fetchAll(),
        'total'       => $totalRows,
        'paginas'     => ceil($totalRows / $limit),
        'pagina_actual' => $page,
    ]);
}

function detalleProducto(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();
    if (!$producto) jsonResponse(['error' => 'No encontrado'], 404);

    $imgs = $pdo->prepare("SELECT id, url, posicion, crop_config FROM producto_imagenes WHERE producto_id = ? ORDER BY posicion");
    $imgs->execute([$id]);
    $producto['imagenes'] = $imgs->fetchAll();

    $cats = $pdo->prepare("SELECT c.id, c.nombre, c.slug FROM producto_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.producto_id = ?");
    $cats->execute([$id]);
    $producto['categorias'] = $cats->fetchAll();

    jsonResponse($producto);
}

function listarCategorias(PDO $pdo): void {
    $stmt = $pdo->query("SELECT c.*, COUNT(pc.producto_id) as total FROM categorias c LEFT JOIN producto_categorias pc ON c.id = pc.categoria_id GROUP BY c.id ORDER BY c.nombre");
    jsonResponse($stmt->fetchAll());
}

function destacados(PDO $pdo): void {
    $stmt = $pdo->query("
        SELECT p.id, p.nombre, p.precio_normal, p.precio_rebajado, p.en_stock, p.slug,
               (SELECT url FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.posicion = 0 LIMIT 1) AS imagen,
               (SELECT crop_config FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.posicion = 0 LIMIT 1) AS imagen_crop
        FROM productos p WHERE p.activo = 1 AND p.en_stock = 1
        ORDER BY RAND() LIMIT 8
    ");
    jsonResponse($stmt->fetchAll());
}
