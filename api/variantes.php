<?php
require_once __DIR__ . '/../includes/funciones.php';

$pdo    = getPDO();
$action = $_GET['action'] ?? '';

match($action) {
    'del_producto'  => variantesProducto($pdo),
    'atributos'     => getAtributos($pdo),
    default         => jsonResponse(['error' => 'Accion no valida'], 400)
};

function variantesProducto(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID requerido'], 400);

    $stmt = $pdo->prepare("
        SELECT pv.*,
               GROUP_CONCAT(CONCAT(a.nombre,': ',av.valor) ORDER BY a.nombre SEPARATOR ' | ') as atributos_label
        FROM producto_variantes pv
        LEFT JOIN variante_atributos va ON va.variante_id = pv.id
        LEFT JOIN atributo_valores av  ON av.id = va.atributo_valor_id
        LEFT JOIN atributos a          ON a.id  = av.atributo_id
        WHERE pv.producto_id = ? AND pv.activo = 1
        GROUP BY pv.id ORDER BY pv.id
    ");
    $stmt->execute([$id]);
    jsonResponse($stmt->fetchAll());
}

function getAtributos(PDO $pdo): void {
    $stmt = $pdo->query("
        SELECT a.id, a.nombre,
               JSON_ARRAYAGG(JSON_OBJECT('id', av.id, 'valor', av.valor)) as valores
        FROM atributos a
        LEFT JOIN atributo_valores av ON av.atributo_id = a.id
        GROUP BY a.id ORDER BY a.nombre
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['valores'] = json_decode($r['valores']);
    }
    jsonResponse($rows);
}
