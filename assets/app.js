// Gestor Central de Estado para la Tienda Pública (AlpineJS)
import { initCart } from './js/app/cart.js?v=20260526';
import { initAuth } from './js/app/auth.js?v=20260526';
import { initProducts } from './js/app/products.js?v=20260526';
import { initCheckout } from './js/app/checkout.js?v=20260526';
import { initRouting } from './js/app/routing.js?v=20260526';
import { initAnalytics } from './js/app/analytics.js?v=20260526';

function app() {
  const base = {
    page: 'home',
    sucursal: 'Balmaceda',
    usuario: null,
    perfil: null,
    productos: [],
    destacados: [],
    categorias: [],
    cart: JSON.parse(localStorage.getItem('mt_cart') || '[]'),
    imagenActiva: null,
    modalCantidad: 1,
    productoDetalle: null,
    cargandoProducto: false,
    descAbierta: true,
    varianteSeleccionada: null,
    atribSeleccionados: {},
    cargando: false,
    filtroQ: '',
    filtroCategoria: '',
    pagina: 1,
    totalPaginas: 1,
    toast: '',
    toastTimer: null,
    nuevaPassword: '',
    newsletterEmail: '',
    perfilTab: 'datos',
    formDireccion: null,
    pedidoClienteDetalle: null,
    loginForm:  { email: '', password: '' },
    loginError: '', loginLoading: false,
    regForm:    { nombre: '', apellido: '', email: '', telefono: '', password: '' },
    regError:   '', regLoading: false,
    checkout: {
      nombre: '', email: '', telefono: '', direccion: '', ciudad: '',
      notas: '', zona_id: '', metodo_pago: 'transferencia',
      metodo_entrega: 'delivery', sucursal_retiro: 'Balmaceda',
      cupon: '', descuento: 0, horario: ''
    },
    checkoutError: '', checkoutLoading: false,
    reviews: [], reviewStats: { promedio: 0, total: 0 },
    fomoViendo: 0, fomoCompras: [],
    productosRelacionados: [],
    popupVisible: false, popupCerrado: false, popupEmail: '',
    favoritos: [],
    blogPosts: [], blogPost: null,
    paginaEstatica: null, cargandoPagina: false,
    listaComunidadCliente: [], cargandoComunidadCliente: false,
    zonas: [],
    config: {},
    textos: {},
    campanaActiva: null,

    async init() {
      await this.checkSesion();
      await Promise.all([this.cargarDestacados(), this.cargarCategorias(), this.cargarBlog(), this.cargarConfig(), this.cargarTextos(), this.cargarCampanaActiva()]);
      this.handleURL();
      window.addEventListener('popstate', () => this.handleURL());
      this.$watch('page', val => this.onPageChange(val));
      this.initPopup();
      if (this.usuario) this.recuperarCarrito();
    },

    base_path: window.MT_BASE_PATH || '',

    getProductImage(url) {
      if (!url) return this.base_path + '/assets/no-image.png';
      if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
      }
      return this.base_path + url;
    },

    slugify(text) {
      if (!text) return '';
      return text
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\--+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
    },

    formatPrecio(n) { return '$' + Number(n).toLocaleString('es-CL'); },

    showToast(msg) {
      this.toast = msg;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => this.toast = '', 2500);
    },

  };

  // Mix in all sub-modules using Object.defineProperties to properly copy getters/setters
  const modules = [
    initCart(),
    initAuth(),
    initProducts(),
    initCheckout(),
    initRouting(),
    initAnalytics()
  ];

  for (const mod of modules) {
    Object.defineProperties(base, Object.getOwnPropertyDescriptors(mod));
  }

  return base;
}

// Exponer la función constructora globalmente para que AlpineJS la localice
window.app = app;
export default app;
