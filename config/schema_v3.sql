-- ============================================================
-- MASCOTIENDAS - Schema v3: SEO, Blog, Delivery, Favoritos
-- ============================================================

-- SEO en productos
ALTER TABLE productos
    ADD COLUMN slug VARCHAR(300) DEFAULT NULL,
    ADD COLUMN meta_titulo VARCHAR(255) DEFAULT NULL,
    ADD COLUMN meta_descripcion VARCHAR(500) DEFAULT NULL,
    ADD COLUMN meta_keywords VARCHAR(300) DEFAULT NULL;

-- SEO en categorias
ALTER TABLE categorias
    ADD COLUMN meta_titulo VARCHAR(255) DEFAULT NULL,
    ADD COLUMN meta_descripcion VARCHAR(500) DEFAULT NULL,
    ADD COLUMN imagen_url VARCHAR(500) DEFAULT NULL;

-- Generar slugs desde nombre (se puede correr una vez)
UPDATE productos SET slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    nombre,' ','-'),'/',''),'.',''),',',''),'(',''),')',''))
WHERE slug IS NULL;

-- Favoritos / Wishlist
CREATE TABLE IF NOT EXISTS favoritos (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    producto_id INT NOT NULL,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fav (usuario_id, producto_id),
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avisos de stock
CREATE TABLE IF NOT EXISTS avisos_stock (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    email       VARCHAR(255) NOT NULL,
    usuario_id  INT DEFAULT NULL,
    enviado     TINYINT(1) NOT NULL DEFAULT 0,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aviso (producto_id, email),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog
CREATE TABLE IF NOT EXISTS blog_posts (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo           VARCHAR(300) NOT NULL,
    slug             VARCHAR(300) NOT NULL UNIQUE,
    extracto         TEXT DEFAULT NULL,
    contenido        LONGTEXT DEFAULT NULL,
    imagen_portada   VARCHAR(500) DEFAULT NULL,
    autor_id         INT DEFAULT NULL,
    publicado        TINYINT(1) NOT NULL DEFAULT 0,
    meta_titulo      VARCHAR(255) DEFAULT NULL,
    meta_descripcion VARCHAR(500) DEFAULT NULL,
    meta_keywords    VARCHAR(300) DEFAULT NULL,
    creado_en        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Páginas estáticas
CREATE TABLE IF NOT EXISTS paginas (
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug             VARCHAR(100) NOT NULL UNIQUE,
    titulo           VARCHAR(255) NOT NULL,
    contenido        LONGTEXT DEFAULT NULL,
    meta_titulo      VARCHAR(255) DEFAULT NULL,
    meta_descripcion VARCHAR(500) DEFAULT NULL,
    activo           TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zonas de delivery
CREATE TABLE IF NOT EXISTS zonas_delivery (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    descripcion VARCHAR(300) DEFAULT NULL,
    costo       INT NOT NULL DEFAULT 0 COMMENT '0 = gratis',
    activo      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Horarios de despacho disponibles
CREATE TABLE IF NOT EXISTS horarios_despacho (
    id       INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dia      TINYINT NOT NULL COMMENT '0=Dom,1=Lun...6=Sab',
    hora_ini TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campos de delivery a pedidos
ALTER TABLE pedidos
    ADD COLUMN zona_delivery_id INT DEFAULT NULL,
    ADD COLUMN costo_delivery INT NOT NULL DEFAULT 0,
    ADD COLUMN fecha_despacho DATE DEFAULT NULL,
    ADD COLUMN hora_despacho VARCHAR(20) DEFAULT NULL,
    ADD COLUMN metodo_entrega ENUM('delivery','retiro') NOT NULL DEFAULT 'delivery';

-- Datos iniciales zonas
INSERT IGNORE INTO zonas_delivery (nombre, descripcion, costo) VALUES
('La Serena Centro', 'Zona urbana La Serena', 0),
('Coquimbo Centro', 'Zona urbana Coquimbo', 0),
('COVICO / Peñuelas', 'Sector periférico', 2000),
('Portal de Pinamar', 'Sector periférico', 2000),
('Fuera de cobertura', 'Consultar disponibilidad', 0);

-- Horarios lunes a domingo
INSERT IGNORE INTO horarios_despacho (dia, hora_ini, hora_fin) VALUES
(1,'09:00','13:00'),(1,'14:00','19:00'),
(2,'09:00','13:00'),(2,'14:00','19:00'),
(3,'09:00','13:00'),(3,'14:00','19:00'),
(4,'09:00','13:00'),(4,'14:00','19:00'),
(5,'09:00','13:00'),(5,'14:00','19:00'),
(6,'09:00','13:00'),(6,'14:00','18:00'),
(0,'10:00','14:00');

-- Páginas estáticas iniciales
INSERT IGNORE INTO paginas (slug, titulo, contenido, meta_titulo, meta_descripcion) VALUES
('politica-devoluciones', 'Política de Devoluciones',
'<h2>Política de Devoluciones</h2><p>De acuerdo a la Ley del Consumidor (Ley N° 19.496) de Chile, tienes derecho a devolver un producto dentro de los <strong>10 días hábiles</strong> desde la recepción si presenta defectos o no corresponde a lo comprado.</p><h3>Condiciones</h3><ul><li>El producto debe estar en su embalaje original sin abrir.</li><li>Debes presentar el comprobante de compra.</li><li>Productos de farmacia veterinaria no tienen devolución salvo defecto de fabricación.</li></ul><h3>Proceso</h3><p>Contáctanos por WhatsApp al +56 9 5379 3135 o escríbenos a ventas@mascotiendas.cl indicando tu número de pedido.</p>',
'Política de Devoluciones | Mascotiendas', 'Conoce nuestra política de devoluciones y cambios según la Ley del Consumidor de Chile.'),
('politica-privacidad', 'Política de Privacidad',
'<h2>Política de Privacidad</h2><p>En Mascotiendas.cl respetamos tu privacidad conforme a la <strong>Ley N° 19.628</strong> sobre Protección de la Vida Privada de Chile.</p><h3>Datos que recopilamos</h3><ul><li>Nombre, email y teléfono al registrarte o hacer un pedido.</li><li>Dirección de despacho para procesar tu pedido.</li><li>Historial de compras para mejorar tu experiencia.</li></ul><h3>Uso de los datos</h3><p>Tus datos se usan exclusivamente para procesar pedidos, enviarte información de tu compra y, si lo autorizas, enviarte ofertas por email. No vendemos ni compartimos tus datos con terceros.</p><h3>Contacto</h3><p>Para ejercer tus derechos de acceso, rectificación o eliminación escríbenos a ventas@mascotiendas.cl.</p>',
'Política de Privacidad | Mascotiendas', 'Política de privacidad y tratamiento de datos personales de Mascotiendas.cl según Ley 19.628.');
