CREATE TABLE IF NOT EXISTS newsletter_suscriptores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nombre VARCHAR(100) DEFAULT NULL,
    estado ENUM('activo', 'desuscrito') DEFAULT 'activo',
    token_desuscripcion VARCHAR(100) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_desuscripcion DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_campanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asunto VARCHAR(255) NOT NULL,
    cuerpo_html LONGTEXT NOT NULL,
    estado ENUM('borrador', 'programada', 'enviada') DEFAULT 'borrador',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_envio DATETIME DEFAULT NULL,
    enviados INT DEFAULT 0,
    abiertos INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carritos_sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_sesion VARCHAR(255) NOT NULL UNIQUE,
    id_usuario INT DEFAULT NULL,
    email_invitado VARCHAR(255) DEFAULT NULL,
    datos_carrito JSON NOT NULL,
    estado ENUM('activo', 'abandonado', 'recuperado', 'completado') DEFAULT 'activo',
    recordatorio_1_enviado BOOLEAN DEFAULT FALSE,
    recordatorio_2_enviado BOOLEAN DEFAULT FALSE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    fecha_desuscripcion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
