-- ============================================================
-- VARIANTES DE PRODUCTOS
-- ============================================================

-- Tipos de atributo (ej: Peso, Sabor, Color)
CREATE TABLE IF NOT EXISTS atributos (
    id     INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores de atributo (ej: 3kg, 10kg, 20kg)
CREATE TABLE IF NOT EXISTS atributo_valores (
    id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    atributo_id  INT NOT NULL,
    valor        VARCHAR(100) NOT NULL,
    FOREIGN KEY (atributo_id) REFERENCES atributos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Variantes del producto
CREATE TABLE IF NOT EXISTS producto_variantes (
    id              INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    producto_id     INT NOT NULL,
    sku             VARCHAR(100) DEFAULT NULL,
    precio_normal   INT NOT NULL,
    precio_rebajado INT DEFAULT NULL,
    stock           INT NOT NULL DEFAULT 0,
    en_stock        TINYINT(1) NOT NULL DEFAULT 1,
    imagen_url      VARCHAR(500) DEFAULT NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relacion variante <-> atributo_valor (ej: variante X tiene Peso=10kg, Sabor=Pollo)
CREATE TABLE IF NOT EXISTS variante_atributos (
    variante_id       INT NOT NULL,
    atributo_valor_id INT NOT NULL,
    PRIMARY KEY (variante_id, atributo_valor_id),
    FOREIGN KEY (variante_id)       REFERENCES producto_variantes(id) ON DELETE CASCADE,
    FOREIGN KEY (atributo_valor_id) REFERENCES atributo_valores(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atributos iniciales comunes para mascotas
INSERT IGNORE INTO atributos (nombre) VALUES
('Peso'), ('Sabor'), ('Cantidad'), ('Especie');

-- Valores de peso comunes
INSERT IGNORE INTO atributo_valores (atributo_id, valor)
SELECT id, v FROM atributos, (
    SELECT '1 kg' v UNION SELECT '1.5 kg' UNION SELECT '2 kg' UNION
    SELECT '3 kg' UNION SELECT '4 kg' UNION SELECT '6 kg' UNION
    SELECT '7.5 kg' UNION SELECT '10 kg' UNION SELECT '12 kg' UNION
    SELECT '15 kg' UNION SELECT '20 kg' UNION SELECT '21 kg' UNION
    SELECT '22 kg' UNION SELECT '25 kg'
) vals WHERE atributos.nombre = 'Peso';

-- Valores de especie
INSERT IGNORE INTO atributo_valores (atributo_id, valor)
SELECT id, v FROM atributos, (
    SELECT 'Perro' v UNION SELECT 'Gato' UNION SELECT 'Ambos'
) vals WHERE atributos.nombre = 'Especie';

-- Columna para marcar si producto tiene variantes
ALTER TABLE productos ADD COLUMN IF NOT EXISTS tiene_variantes TINYINT(1) NOT NULL DEFAULT 0;
