-- ============================================================
-- MASCOTIENDAS - Tablas adicionales (usuarios, pedidos, etc.)
-- Ejecutar DESPUÉS de mascotiendas_productos.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(150) NOT NULL,
    apellido      VARCHAR(150) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono      VARCHAR(20)  DEFAULT NULL,
    rol           ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS direcciones (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT          NOT NULL,
    alias       VARCHAR(100) DEFAULT 'Casa',
    calle       VARCHAR(255) NOT NULL,
    numero      VARCHAR(20)  NOT NULL,
    depto       VARCHAR(50)  DEFAULT NULL,
    ciudad      VARCHAR(100) NOT NULL,
    region      VARCHAR(100) NOT NULL,
    predeterminada TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
    id              INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT          DEFAULT NULL,
    nombre_cliente  VARCHAR(255) NOT NULL,
    email_cliente   VARCHAR(255) NOT NULL,
    telefono        VARCHAR(20)  DEFAULT NULL,
    direccion       TEXT         NOT NULL,
    ciudad          VARCHAR(100) NOT NULL,
    sucursal        VARCHAR(100) DEFAULT NULL,
    subtotal        INT          NOT NULL,
    descuento       INT          NOT NULL DEFAULT 0,
    total           INT          NOT NULL,
    estado          ENUM('pendiente','pagado','preparando','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    metodo_pago     VARCHAR(50)  DEFAULT 'flow',
    flow_token      VARCHAR(255) DEFAULT NULL,
    notas           TEXT         DEFAULT NULL,
    creado_en       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_items (
    id           INT  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pedido_id    INT  NOT NULL,
    producto_id  INT  NOT NULL,
    nombre       VARCHAR(500) NOT NULL,
    precio       INT  NOT NULL,
    cantidad     INT  NOT NULL DEFAULT 1,
    imagen_url   VARCHAR(1000) DEFAULT NULL,
    FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter (
    id        INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email     VARCHAR(255) NOT NULL UNIQUE,
    activo    TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin por defecto (password: Admin1234!)
INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol)
VALUES ('Mascotiendas', 'Admin', 'admin@mascotiendas.cl',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
