<?php
// Controlador del Blog para el Panel de Administración

if (!defined('MASCOTIENDAS_ADMIN_ROUTE')) {
    exit('No direct script access allowed');
}

// ─── BLOG ────────────────────────────────────────────────
function blogList(PDO $pdo): void {
    $stmt = $pdo->query("SELECT id,titulo,slug,imagen_portada,publicado,creado_en FROM blog_posts ORDER BY creado_en DESC");
    jsonResponse($stmt->fetchAll());
}

function blogSave(PDO $pdo): void {
    $id       = (int)($_POST['id'] ?? 0);
    $titulo   = sanitize($_POST['titulo'] ?? '');
    $slug     = sanitize($_POST['slug'] ?? '');
    $extracto = $_POST['extracto'] ?? '';
    $contenido= $_POST['contenido'] ?? '';
    $imagen   = sanitize($_POST['imagen_portada'] ?? '');
    $meta_t   = sanitize($_POST['meta_titulo'] ?? '');
    $meta_d   = $_POST['meta_descripcion'] ?? '';
    $publicado= (int)($_POST['publicado'] ?? 0);
    $autor_id = $_SESSION['usuario_id'];

    if (!$titulo || !$slug) jsonResponse(['error' => 'Titulo y slug requeridos'], 400);

    if ($id) {
        $pdo->prepare("UPDATE blog_posts SET titulo=?,slug=?,extracto=?,contenido=?,imagen_portada=?,meta_titulo=?,meta_descripcion=?,publicado=? WHERE id=?")
            ->execute([$titulo,$slug,$extracto,$contenido,$imagen,$meta_t,$meta_d,$publicado,$id]);
    } else {
        $pdo->prepare("INSERT INTO blog_posts (titulo,slug,extracto,contenido,imagen_portada,meta_titulo,meta_descripcion,publicado,autor_id) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$titulo,$slug,$extracto,$contenido,$imagen,$meta_t,$meta_d,$publicado,$autor_id]);
        $id = (int)$pdo->lastInsertId();
    }
    jsonResponse(['ok' => true, 'id' => $id]);
}

function blogDelete(PDO $pdo): void {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
}
