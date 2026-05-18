# Mascotiendas 🐾

Tienda de mascotas online para La Serena y Coquimbo. Venta de comida para perros y gatos, farmacia veterinaria, arena sanitaria y snacks premium con delivery gratis en zona urbana.

## Stack

- PHP 8+ (sin framework)
- MySQL con PDO
- Tailwind CSS (CDN)
- Alpine.js (CDN)
- Font Awesome

## Estructura

```
public_html/
├── admin/          # Panel de administración
├── api/            # Endpoints REST (JSON)
├── assets/         # JS y recursos estáticos
├── config/         # Schemas SQL
├── includes/       # Componentes reutilizables (header, footer, etc.)
├── views/          # Vistas del frontend
├── index.php       # Entry point + sitemap
└── .htaccess       # Rewrite rules
```

## Configuración

Crear `config/db.php` con las credenciales de la base de datos (no incluido en el repo):

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tu_base_de_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
```

Importar los schemas en orden desde `config/`:

1. `schema_admin.sql`
2. `schema_v3.sql` → `schema_v4.sql`
3. `schema_extra.sql`
4. `schema_variantes.sql`
5. `schema_configuraciones.sql`
6. `schema_textos_campanas.sql`

## Contacto

- Web: [mascotiendas.cl](https://mascotiendas.cl)
- WhatsApp: +569 5379 3135
- Email: ventas@mascotiendas.cl
