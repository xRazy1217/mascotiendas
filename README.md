# Mascotiendas 🐾

Tienda de mascotas online para La Serena y Coquimbo. Venta de comida para perros y gatos, farmacia veterinaria, arena sanitaria y snacks premium con delivery gratis en zona urbana.

---

## Stack de Tecnologías

- **Lenguaje**: PHP 8+ (desarrollo modular, sin framework)
- **Base de Datos**: MySQL (interactuando mediante PDO y transacciones atómicas)
- **Frontend**: Tailwind CSS y Alpine.js (cargados mediante recursos locales en `/assets/vendor/` para garantizar la velocidad, privacidad y evitar dependencias de CDN externos)
- **Iconografía**: Font Awesome (local)
- **Analíticas e Tracking**: Soporte integrado para Google Tag Manager, Google Analytics 4 y conversiones de Google Ads.

---

## Estructura del Proyecto

```
mascotiendas/
├── admin/                  # Panel de administración (SPA)
│   └── views/              # Vistas del panel (dashboard, productos, pedidos, etc.)
├── api/                    # Endpoints REST (JSON)
│   ├── admin/              # Submódulos controladores para la API de administración
│   │   ├── productos.php
│   │   ├── pedidos.php
│   │   ├── usuarios.php
│   │   ├── marketing.php
│   │   ├── blog.php
│   │   └── config.php
│   ├── admin.php           # Enrutador principal de la API de administración
│   ├── auth.php            # Endpoint de autenticación y sesiones
│   ├── cron_carritos.php   # Cron para recuperación de carritos abandonados
│   ├── pedidos.php         # Endpoint público para creación e historial de pedidos
│   ├── productos.php       # Catálogo, categorías y búsqueda pública
│   └── ...                 # Otros endpoints (direcciones, favoritos, reviews)
├── assets/                 # Scripts JavaScript y estilos CSS
│   ├── js/
│   │   ├── admin/          # Módulos de administración (products, orders, etc.)
│   │   ├── app/            # Módulos de la app cliente (cart, checkout, routing, etc.)
│   │   ├── admin.js        # Inicializador del panel de administración
│   │   └── app.js          # Inicializador de la aplicación del cliente
│   ├── vendor/             # Librerías de terceros locales (alpine.js, tailwind.js)
│   └── index.css           # Estilos personalizados y utilidades de diseño premium
├── config/                 # Schemas SQL de base de datos
├── feeds/                  # Feeds XML para Google Shopping y Merchant Center
├── includes/               # Funciones del core y helpers globales (funciones.php, db.php)
├── uploads/                # Archivos subidos por usuarios y catálogo (imágenes locales WebP)
├── views/                  # Componentes y vistas HTML del frontend cliente
├── index.php               # Entry point principal (Enrutador de URLs amigables)
├── robots.txt              # Configuración de rastreo para motores de búsqueda
├── tests/                  # Suite de pruebas automatizadas e integración
└── .htaccess               # Configuración de reescritura Apache y headers de seguridad
```

---

## Instalación y Configuración

### 1. Configuración de Base de Datos
Crear el archivo `config/db.php` con las credenciales de conexión locales:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mascotiendas');
define('DB_USER', 'root');
define('DB_PASS', '');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            die(json_encode(['error' => 'Error de conexión a la base de datos. Por favor, intente más tarde.']));
        }
    }
    return $pdo;
}
```

### 2. Importación de Esquemas de Base de Datos
Ejecutar los scripts SQL ubicados en `config/` en el siguiente orden secuencial:
1. `schema_admin.sql` (Creación de tablas administrativas básicas)
2. `schema_v3.sql` / `schema_v4.sql` (Tablas principales del catálogo)
3. `schema_extra.sql` (Direcciones, favoritos, valoraciones)
4. `schema_variantes.sql` (Control de variantes de producto)
5. `schema_configuraciones.sql` (Parámetros generales del sistema)
6. `schema_textos_campanas.sql` (Textos e imágenes personalizables de la Home)
7. `schema_marketing.sql` (Tablas para boletín y carritos abandonados)
8. `schema_comunidad.sql` (Tablas para el contenido dinámico de comunidad y eventos)

---

## Flujos y Características Clave

### 1. Flujo de Compra y Persistencia de Pedidos
- **Formulario de Checkout**: Recopila la información personal del cliente (nombre, correo electrónico, teléfono), dirección y ciudad de entrega (delivery), sucursal física (retiro) e indicaciones o notas especiales.
- **Persistencia Directa**: Antes de redirigir al canal de atención, el frontend realiza una petición asíncrona a `/api/pedidos.php?action=crear` para guardar la orden en la base de datos (tablas `pedidos` y `pedido_items`).
- **Control Financiero**: El backend verifica directamente los precios contra el catálogo para prevenir modificaciones fraudulentas en cliente, calcula el total exacto aplicando la zona de despacho correspondiente y los descuentos activos, y devuelve un ID correlativo oficial (ej. `N° 56`).
- **Derivación a WhatsApp**: Se abre una pestaña con el mensaje preformateado conteniendo el ID de orden real, detalles del cliente, ítems del pedido, método de pago coordinado (Transferencia o Efectivo) y total a pagar.

### 2. SEO Técnico, Indexación y URLs Amigables
- **Rewrite Rules**: Apache reescribe de forma limpia las rutas del catálogo (ej. `/tienda`, `/farmacia`, `/producto/189-slug-producto`, `/blog/slug-post`, `/pagina/slug-pagina`) redireccionando a `index.php`.
- **Higiene de Rastreo (Meta Robots Dinámico)**: El servidor inyecta `<meta name="robots" content="index, follow">` para las rutas públicas principales. Para páginas privadas, utilidades de usuario y el flujo de compra (`/login`, `/registro`, `/carrito`, `/checkout`, `/perfil`, `/favoritos`), inyecta dinámicamente `noindex, nofollow`. Asimismo, la zona `/admin/` está protegida a nivel de cabecera con `noindex, nofollow`.
- **Estructura Semántica (Un único <h1>)**: Cada vista activa expone exactamente un tag `<h1>` (título del Hero en Inicio, Catálogo en Tienda, Farmacia Veterinaria, etc.). En el detalle del blog, el título de la sección se transforma de `<h1>` a `<span>` dinámicamente para que el título del post individual sea el único `<h1>` del documento.
- **Metas y JSON-LD**: El servidor inyecta dinámicamente títulos optimizados, canonicals, metatags de Open Graph / Twitter Cards y fragmentos enriquecidos (Schema.org) tipo `Product` (con precio, disponibilidad y valoraciones), `PetStore`, `BreadcrumbList` y `WebSite` con buscador integrado.
- **Sindicación**: Generación automatizada de `/sitemap.xml` (incluyendo URLs amigables de categorías y productos con sus imágenes asociadas) y del feed de productos `/feed/google-shopping.xml` (RSS 2.0 adaptado a Google Merchant Center).

### 3. Editor de Catálogo y Recorte de Imágenes
- **Personalización de Recortes**: El panel de administración permite configurar de manera visual el zoom y encuadre (posición X/Y) de cada imagen de producto para las vistas de Catálogo, Ficha de Detalle y Miniatura de Carrito.
- **Almacenamiento y Optimización**: Las imágenes se almacenan localmente en `/uploads/` y se procesan mediante consola a formato **WebP** comprimido a un ancho/alto máximo de `800px` con un 80% de calidad.

### 4. Boletines y Marketing Automatizado
- **Suscripciones**: Registro automático en boletín con enlaces rápidos de desuscripción mediante tokens cifrados en 1 clic.
- **Carritos Abandonados**: Envío programado mediante cron (`api/cron_carritos.php`) con sistema de exclusión (Blacklist) administrable para evitar contactar a clientes que solicitaron no recibir promociones.

---

## Seguridad e Infraestructura (Hardening)

1. **Tokens CSRF**: Todas las peticiones de escritura (POST, PUT, DELETE) requieren un token CSRF válido que viaja en la cabecera `X-CSRF-Token` y se valida en `checkCSRF()`.
2. **Defensas de Sesión**: Las cookies se configuran con propiedades estrictas (`httponly`, `use_only_cookies`, y `secure` dinámico bajo HTTPS) y se regeneran los identificadores de sesión (`session_regenerate_id(true)`) al autenticar para evitar secuestro de sesiones.
3. **Restricción de Scripts**:
   - `api/db_init.php` restringido a ejecución local CLI únicamente.
   - Directorio `/uploads/` protegido con `.htaccess` para prohibir la ejecución de scripts (mitiga cargas de Web Shells).
   - Cabeceras de protección HTTP configuradas en la raíz (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`).
4. **Control de Errores**: Ocultación de fugas de datos de bases de datos mediante el control centralizado de excepciones PDO.

---

## Pruebas de Integración

El proyecto cuenta con una suite completa de pruebas funcionales y de seguridad basada en consola (CLI):
```powershell
php tests/massive_tests.php
```
Las pruebas simulan un navegador cliente realizando compras, iniciando sesión, modificando direcciones y enviando valoraciones, así como simulaciones de inyección de código XSS y ataques CSRF sin token.
