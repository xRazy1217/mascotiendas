// Gestor Central de Estado para el Panel de Administración (AlpineJS)
import { initProducts } from './js/admin/products.js?v=20260526_2';
import { initOrders } from './js/admin/orders.js?v=20260526_2';
import { initMarketing } from './js/admin/marketing.js?v=20260526_2';
import { initDashboard } from './js/admin/dashboard.js?v=20260526_2';
import { initCategories } from './js/admin/categories.js?v=20260529_1';
import { initComunidad } from './js/admin/comunidad.js?v=20260529_1';

function admin() {
  return {
    seccion: 'dashboard',
    stats: {},
    bajoStockList: [],
    // Productos
    listaProductos: [], prodQ: '', prodPagina: 1, prodPaginas: 1, prodFiltroStock: '', prodOrden: 'id_desc',
    formProducto: null,
    // Pedidos
    listaPedidos: [], pedidoFiltro: '', pedidoQ: '', pedidoPagina: 1, pedidoPaginas: 1, totalPedidos: 0,
    pedidoDetalle: null,
    // Usuarios
    listaUsuarios: [], usuarioQ: '',
    // Cupones
    listaCupones: [], formCupon: null,
    // Reviews
    listaReviews: [], reviewFiltro: -1,
    // Blog
    listaBlog: [], formPost: null,
    // Variantes
    atributos: [], formVariante: null,
    // Marketing
    listaNewsletter: [],
    seccionMarketingTab: 'resumen',
    listaCarritos: [],
    listaBlacklist: [],
    campanaAsunto: '',
    campanaMensaje: '',
    // Notificaciones
    listaNotif: [],
    // Configuracion
    config: {},
    // Textos
    textos: {},
    // Campanas
    listaCampanas: [], formCampana: null,
    // UI
    toast: '', toastTimer: null,
    // Recorte de Imagen
    modalRecorte: null,
    recorteTab: 'catalogo',
    recorteConfig: null,

    async init() {
      await this.cargarStats();
      await this.cargarBajoStock();
      await this.cargarConfig();
      setInterval(() => this.cargarStats(), 60000);
    },

    base_path: window.MT_BASE_PATH || '',

    getProductImage(url) {
      if (!url) return this.base_path + '/assets/no-image.png';
      if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
      }
      return this.base_path + url;
    },

    formatPrecio(n) { return '$' + Number(n).toLocaleString('es-CL'); },

    showToast(msg) {
      this.toast = msg;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => this.toast = '', 2500);
    },

    // Integrar submódulos por descomposición
    ...initProducts(),
    ...initOrders(),
    ...initMarketing(),
    ...initDashboard(),
    ...initCategories(),
    ...initComunidad()
  };
}

// Exponer la función constructora globalmente para que AlpineJS la localice
window.admin = admin;
export default admin;
