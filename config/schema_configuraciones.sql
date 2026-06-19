CREATE TABLE IF NOT EXISTS configuraciones (
    clave VARCHAR(100) NOT NULL PRIMARY KEY,
    valor TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('descuento_primer_pedido', '1');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('descuento_primer_pedido_valor', '10');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('popup_activo', '1');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('popup_titulo', '¡Espera! 10% de descuento');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('popup_texto', 'Suscríbete y recibe tu cupón de descuento al instante');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('popup_descuento_valor', '10% de descuento');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('marketing_automatizacion_activo', '1');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('theme_color_primary', '#7F5234');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('theme_color_secondary', '#F7941D');
INSERT IGNORE INTO configuraciones (clave, valor) VALUES ('theme_color_cream', '#F9F1E7');
