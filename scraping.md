
Listado de productos
194 productos con nombre, precio, descripción larga y corta, imágenes. Ya exportados del CSV.
Galería de imágenes
319 imágenes mapeadas del XML. 54 productos con 2+ fotos. Ya tienes las instrucciones para Amazon Q.
Filtro por categoría
8 categorías principales en el menú real: Comida perros, Comida gatos, Farmacia, Arena, Snack Premium.
Etiquetas de productos
157 product tags en el XML (por peso, especie, marca). Permiten filtros secundarios más finos.
Estado de stock
143 en stock, 51 sin stock según el XML. La maqueta actual no muestra este estado — hay que agregarlo.
Filtro por marca
30+ marcas en el XML (Dockennedy, Pro Plan, Monge, Sense, Agility, Hills…). El WP no las filtraba bien.
Buscador con sugerencias
El WP no tenía search funcional. Implementar búsqueda con sugerencias en tiempo real en Laravel.
Pedidos y checkout
Botón WhatsApp por producto
El WP usaba el plugin WA Order con el número +56 9 5379 3135. El mismo flujo debe existir en Laravel.
Transferencia bancaria
El 81% de los 117 pedidos históricos fue por transferencia directa. Debe ser el método principal.
Pago contra entrega
El 19% de pedidos fue en efectivo al momento del delivery. Debe mantenerse como opción.
Carrito de compras
Existía en WP vía WooCommerce. La maqueta ya lo tiene básico — falta persistencia en sesión/BD.
Checkout con dirección
Los pedidos del XML tienen billing/shipping con ciudad, dirección, teléfono. Hay que construir el form completo.
Selector de sucursal en pedido
El WP no lo tenía bien implementado. La maqueta lo tiene en el header — debe vincularse al checkout.
Cupones de descuento
Había 1 cupón "descuento especial" ($5.000 fijo) con 0 usos. Implementar sistema de cupones en Laravel.
Tracking de pedido
El WP tenía estados (processing, on-hold, completed, cancelled) pero sin vista para el cliente.
Delivery
Delivery gratis en zona urbana
Era la propuesta principal del sitio. Zona: La Serena y Coquimbo urbano. Debe estar muy visible en el nuevo sitio.
Zonas de cobertura
El WP no tenía mapa de zonas claro. Los pedidos muestran clientes en COVICO, Peñuelas, Portal de Pinamar — fuera del centro.
Validador de dirección
Al ingresar dirección en checkout, validar si está en zona de delivery gratis o tiene costo. Evitar confusiones.
Agenda de despachos
El blog decía "lunes a domingo abierto, delivery todos los días". Agregar selector de día/hora de entrega.
Usuarios y clientes
Portal de clientes
El WP tenía 2 URLs de portal (Jetpack CRM). Hay que rehacer esto en Laravel: ver pedidos anteriores, datos.
Historial de pedidos
117 pedidos en el XML con nombre, email, teléfono, dirección. Se pueden migrar para que clientes vean su historial.
Registro y login
Había "Mi cuenta" como página draft. Construir auth completo (registro, login, recuperar contraseña).
Lista de favoritos
No existía. Agregar wishlist para que usuarios guarden productos. Aumenta retención y conversión.
Notificaciones de stock
51 productos sin stock. Botón "Avísame cuando llegue" — email al cliente cuando el producto vuelve.
Contenido, SEO y comunidad
Blog
2 posts publicados en WP. Portar contenido existente y crear sección blog en Laravel para nuevos artículos.
Política de devoluciones
Existía como draft en WP. Publicarla en el nuevo sitio — es obligatoria legalmente en Chile (Ley del Consumidor).
Política de privacidad
También estaba como draft. Publicarla — obligatoria por la Ley 19.628 de datos personales en Chile.
SEO por producto
El WP usaba Rank Math con meta títulos y keywords por producto (score 59 en promedio). Portar meta tags a Laravel.
Sección Comunidad / Eventos
La maqueta ya tiene "Corridas caninas 2026". Conectar con formulario de inscripción real.
Newsletter
El WP tenía el form en el footer pero sin integración. Conectar con Mailchimp o similar.
Panel de administración
Gestión de pedidos
Ver pedidos nuevos, cambiar estado (pendiente → en camino → entregado), notificar al cliente por WhatsApp o email.
Gestión de stock
Actualizar cantidades de los 51 productos sin stock. El WP solo tenía 1 producto con stock numérico real.
Gestión de cupones
Crear, editar y deshabilitar cupones. El único cupón existente nunca fue usado — simplificar el flujo de creación.
Dashboard de ventas
El WP no lo tenía. Métricas simples: pedidos del día, ticket promedio ($39.901 histórico), productos más vendidos.
