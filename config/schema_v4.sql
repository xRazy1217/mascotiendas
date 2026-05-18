-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id  INT DEFAULT NULL,
    nombre      VARCHAR(150) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    estrellas   TINYINT NOT NULL DEFAULT 5,
    comentario  TEXT DEFAULT NULL,
    aprobado    TINYINT(1) NOT NULL DEFAULT 0,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carrito abandonado
CREATE TABLE IF NOT EXISTS carritos_abandonados (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT DEFAULT NULL,
    email       VARCHAR(255) DEFAULT NULL,
    items       JSON NOT NULL,
    total       INT NOT NULL DEFAULT 0,
    recuperado  TINYINT(1) NOT NULL DEFAULT 0,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics visitas
CREATE TABLE IF NOT EXISTS analytics (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo        VARCHAR(50) NOT NULL COMMENT 'vista_producto, vista_pagina, carrito, checkout',
    referencia  VARCHAR(255) DEFAULT NULL COMMENT 'producto_id, pagina slug',
    session_id  VARCHAR(100) DEFAULT NULL,
    usuario_id  INT DEFAULT NULL,
    ip          VARCHAR(45) DEFAULT NULL,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FOMO: actividad reciente
CREATE TABLE IF NOT EXISTS fomo_eventos (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo        ENUM('compra','vista') NOT NULL DEFAULT 'compra',
    nombre      VARCHAR(100) DEFAULT NULL COMMENT 'nombre anonimizado',
    ciudad      VARCHAR(100) DEFAULT NULL,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Metodos de pago en pedidos
ALTER TABLE pedidos ADD COLUMN metodo_pago_detalle VARCHAR(100) DEFAULT NULL;
