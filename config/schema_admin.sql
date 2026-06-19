-- Cupones de descuento
CREATE TABLE IF NOT EXISTS cupones (
    id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    codigo        VARCHAR(50)  NOT NULL UNIQUE,
    tipo          ENUM('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
    valor         INT          NOT NULL,
    minimo_compra INT          NOT NULL DEFAULT 0,
    usos_max      INT          DEFAULT NULL,
    usos_actual   INT          NOT NULL DEFAULT 0,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    expira_en     DATE         DEFAULT NULL,
    creado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificaciones admin
CREATE TABLE IF NOT EXISTS notificaciones (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo        VARCHAR(50)  NOT NULL,
    titulo      VARCHAR(255) NOT NULL,
    mensaje     TEXT         NOT NULL,
    leida       TINYINT(1)   NOT NULL DEFAULT 0,
    url         VARCHAR(255) DEFAULT NULL,
    creado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar inventario y stock_minimo a productos si no existen
ALTER TABLE productos 
    ADD COLUMN stock_minimo INT NOT NULL DEFAULT 5,
    ADD COLUMN inventario_actual INT NOT NULL DEFAULT 0;

-- Cupones de ejemplo
INSERT IGNORE INTO cupones (codigo, tipo, valor, minimo_compra, usos_max, activo) VALUES
('BIENVENIDO10', 'porcentaje', 10, 0, 100, 1),
('DESCUENTO5000', 'monto_fijo', 5000, 20000, 50, 1);
