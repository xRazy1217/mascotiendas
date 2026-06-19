CREATE TABLE IF NOT EXISTS comunidad_contenido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    imagen_url VARCHAR(255) DEFAULT NULL,
    enlace_url VARCHAR(255) DEFAULT NULL,
    cta_texto VARCHAR(100) DEFAULT 'Participar ahora',
    fecha_evento DATE DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO comunidad_contenido (id, titulo, descripcion, imagen_url, cta_texto, enlace_url, activo)
VALUES (1, 'Corridas Caninas 2026', 'Únete a la gran corrida familiar con tu mascota en la conurbación La Serena - Coquimbo. Habrá premios, hidratación canina y sorpresas.', 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=1200', 'Participar ahora', 'https://wa.me/56953793135?text=Hola!+Quiero+inscribirme+en+la+corrida+canina', 1);
