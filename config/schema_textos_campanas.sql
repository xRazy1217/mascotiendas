CREATE TABLE IF NOT EXISTS textos (
    clave   VARCHAR(100) NOT NULL PRIMARY KEY,
    valor   TEXT         NOT NULL,
    tipo    ENUM('texto','imagen','url') NOT NULL DEFAULT 'texto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campanas (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    titulo      VARCHAR(255) NOT NULL,
    texto       TEXT         DEFAULT NULL,
    imagen_url  VARCHAR(500) DEFAULT NULL,
    btn_texto   VARCHAR(100) DEFAULT NULL,
    btn_url     VARCHAR(300) DEFAULT NULL,
    activacion  ENUM('exit','entrada','segundos') NOT NULL DEFAULT 'exit',
    segundos    INT          NOT NULL DEFAULT 5,
    fecha_ini   DATE         DEFAULT NULL,
    fecha_fin   DATE         DEFAULT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Textos iniciales
INSERT IGNORE INTO textos (clave, valor, tipo) VALUES
('hero_titulo',       'AMOR EN CADA BOCADO.', 'texto'),
('hero_subtitulo',    'Comida para perros y gatos · Arena sanitaria · Farmacia veterinaria', 'texto'),
('hero_imagen',       'https://images.unsplash.com/photo-1537151608828-ea2b11777ee8?auto=format&fit=crop&w=500&h=500', 'imagen'),
('delivery_titulo',   'Nuestro delivery es exclusivo en la conurbación La Serena – Coquimbo', 'texto'),
('delivery_texto',    'Delivery gratis en todo La Serena y Coquimbo. Consulta cobertura por WhatsApp antes de comprar.', 'texto'),
('delivery_imagen',   'https://mascotiendas.cl/wp-content/uploads/2025/02/IMG-20250130-WA0049-768x768.jpg', 'imagen'),
('footer_email',      'ventas@mascotiendas.cl', 'texto'),
('footer_telefono',   '+569 5379 3135', 'texto'),
('footer_direccion',  'Av. Balmaceda 4521 Local #2, La Serena', 'texto'),
('footer_instagram',  'https://instagram.com', 'url'),
('footer_facebook',   'https://facebook.com', 'url'),
('info_item1',        'Comida Perros y Gatos', 'texto'),
('info_item2',        'Farmacia Veterinaria', 'texto'),
('info_item3',        'Delivery Gratis', 'texto'),
('info_item4',        '+569 5379 3135', 'texto'),
('cat_perros_imagen',   'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=400', 'imagen'),
('cat_gatos_imagen',    'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=400', 'imagen'),
('cat_arena_imagen',    'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=400', 'imagen'),
('cat_farmacia_imagen', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=400', 'imagen');
