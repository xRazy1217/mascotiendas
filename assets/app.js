function app() {
  return {
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

    handleURL() {
      const params = new URLSearchParams(window.location.search);
      const p    = params.get('p') || 'home';
      const id   = params.get('id');
      const slug = params.get('slug');
      const pages = ['home','tienda','farmacia','comunidad','carrito','checkout','login','registro','perfil','favoritos','blog','pagina','producto'];
      if (p === 'producto' && id)       { this.abrirProducto(id, false); }
      else if (p === 'pagina' && slug)  { this.abrirPagina(slug, false); }
      else if (pages.includes(p))       { this.page = p; }
      else                              { this.page = 'home'; }
    },

    pushURL(p, extra) {
      const params = new URLSearchParams({ p });
      if (extra) Object.entries(extra).forEach(([k,v]) => params.set(k, v));
      history.pushState({}, '', '/?' + params.toString());
    },

    onPageChange(val) {
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
      const actual = new URLSearchParams(window.location.search).get('p') || 'home';
      if (actual !== val) this.pushURL(val);
      if (val === 'tienda')    { this.filtroQ = ''; this.filtroCategoria = ''; this.pagina = 1; this.cargarProductos('tienda'); }
      if (val === 'farmacia')  { this.pagina = 1; this.cargarProductos('farmacia'); }
      if (val === 'perfil' && this.usuario) this.checkSesion();
      if (val === 'favoritos') this.cargarFavoritos();
      if (val === 'blog')      this.cargarBlog();
      if (val === 'checkout')  this.cargarZonas();
    },

    sucursalNombre() {
      return { Balmaceda: 'Balmaceda 4521', Geronimo: 'Geronimo Mendez', Alessandri: 'Alessandri 147' }[this.sucursal] || this.sucursal;
    },

    get cartCount() { return this.cart.reduce((s, i) => s + i.cantidad, 0); },
    cartTotal()     { return this.cart.reduce((s, i) => s + (i.precio * i.cantidad), 0); },
    formatPrecio(n) { return '$' + Number(n).toLocaleString('es-CL'); },
    saveCart()      { localStorage.setItem('mt_cart', JSON.stringify(this.cart)); },

    totalConDescuento() {
      const zona     = this.zonas.find(z => z.id == this.checkout.zona_id);
      const delivery = (this.checkout.metodo_entrega === 'delivery' && zona) ? (parseInt(zona.costo) || 0) : 0;
      return Math.max(0, this.cartTotal() + delivery - (this.checkout.descuento || 0));
    },

    addToCart(p) {
      const precio = p.precio_rebajado || p.precio_normal;
      const existe = this.cart.find(i => i.id == p.id);
      if (existe) { existe.cantidad++; }
      else { this.cart.push({ id: p.id, nombre: p.nombre, precio, imagen: p.imagen || null, cantidad: 1 }); }
      this.saveCart();
      this.showToast('Agregado al carrito');
    },

    showToast(msg) {
      this.toast = msg;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => this.toast = '', 2500);
    },

    async cargarDestacados() {
      const r = await fetch('/api/productos.php?action=destacados');
      this.destacados = await r.json();
    },

    async cargarCategorias() {
      const r = await fetch('/api/productos.php?action=categorias');
      this.categorias = await r.json();
    },

    async cargarProductos(tipo) {
      this.cargando = true;
      const t = tipo || (this.page === 'farmacia' ? 'farmacia' : 'tienda');
      const params = new URLSearchParams({
        action: 'list', page: this.pagina,
        q: this.filtroQ, categoria: this.filtroCategoria, tipo: t
      });
      const r = await fetch('/api/productos.php?' + params);
      const d = await r.json();
      this.productos    = d.productos;
      this.totalPaginas = d.paginas;
      this.cargando     = false;
    },

    async abrirProducto(id, push = true) {
      this.page                 = 'producto';
      this.cargandoProducto     = true;
      this.productoDetalle      = null;
      this.varianteSeleccionada = null;
      this.atribSeleccionados   = {};
      this.reviews              = [];
      this.reviewStats          = { promedio: 0, total: 0 };
      this.fomoViendo           = 0;
      this.fomoCompras          = [];
      this.productosRelacionados = [];
      this.modalCantidad        = 1;
      this.descAbierta          = true;
      if (push) this.pushURL('producto', { id });
      const r = await fetch('/api/productos.php?action=detalle&id=' + id);
      const d = await r.json();
      if (d.tiene_variantes) {
        const rv = await fetch('/api/admin.php?action=variantes_list&producto_id=' + id);
        d.variantes = await rv.json();
        d.grupos_atributos = this.agruparAtributos(d.variantes);
      }
      this.productoDetalle  = d;
      this.imagenActiva     = d.imagenes?.[0]?.url || d.imagen || null;
      this.cargandoProducto = false;
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
      // Cargar datos adicionales en paralelo
      this.cargarReviews(id);
      this.cargarFomo(id);
      this.cargarRelacionados(id);
      this.trackVista(id);
    },

    agruparAtributos(variantes) {
      const grupos = {};
      variantes.forEach(v => {
        if (!v.label) return;
        v.label.split(' | ').forEach(part => {
          const [nombre, valor] = part.split(': ');
          if (!grupos[nombre]) grupos[nombre] = { nombre, opciones: [] };
          if (!grupos[nombre].opciones.find(o => o.valor === valor)) {
            grupos[nombre].opciones.push({ valor, valor_id: v.id, disponible: !!v.en_stock });
          }
        });
      });
      return Object.values(grupos);
    },

    seleccionarVariante(varianteId) {
      const v = this.productoDetalle.variantes?.find(x => x.id == varianteId);
      if (v) {
        this.varianteSeleccionada = v;
        if (v.imagen_url) this.imagenActiva = v.imagen_url;
      }
    },

    addToCartDetalle() {
      const p      = this.varianteSeleccionada || this.productoDetalle;
      const precio = p.precio_rebajado || p.precio_normal;
      const imagen = this.imagenActiva || this.productoDetalle.imagenes?.[0]?.url || null;
      const itemId = this.varianteSeleccionada ? 'v_' + this.varianteSeleccionada.id : this.productoDetalle.id;
      const nombre = this.varianteSeleccionada
        ? this.productoDetalle.nombre + ' (' + (this.varianteSeleccionada.label || '') + ')'
        : this.productoDetalle.nombre;
      const existe = this.cart.find(i => i.id == itemId);
      if (existe) { existe.cantidad += this.modalCantidad; }
      else { this.cart.push({ id: itemId, producto_id: this.productoDetalle.id, nombre, precio, imagen, cantidad: this.modalCantidad }); }
      this.saveCart();
      this.guardarCarritoAbandonado();
      this.showToast(this.modalCantidad + ' producto(s) agregado(s) al carrito');
    },

    // ─── REVIEWS ─────────────────────────────────────────
    async cargarReviews(id) {
      const r = await fetch('/api/reviews.php?action=del_producto&producto_id=' + id);
      const d = await r.json();
      this.reviews     = d.reviews || [];
      this.reviewStats = d.stats   || { promedio: 0, total: 0 };
    },

    async enviarReview(productoId, form, callback) {
      const fd = new FormData();
      fd.append('action', 'crear');
      fd.append('producto_id', productoId);
      Object.entries(form).forEach(([k,v]) => fd.append(k, v));
      const r = await fetch('/api/reviews.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.error) callback('Error: ' + d.error);
      else { callback(d.mensaje); this.cargarReviews(productoId); }
    },

    // ─── FOMO ────────────────────────────────────────────
    async cargarFomo(id) {
      const r = await fetch('/api/tracking.php?action=fomo&producto_id=' + id);
      const d = await r.json();
      this.fomoViendo  = d.viendo_ahora || 0;
      this.fomoCompras = d.compras_recientes || [];
    },

    tiempoRelativo(fecha) {
      const diff = Math.floor((Date.now() - new Date(fecha)) / 60000);
      if (diff < 1)  return 'hace un momento';
      if (diff < 60) return 'hace ' + diff + ' min';
      const h = Math.floor(diff / 60);
      if (h < 24)    return 'hace ' + h + ' hora' + (h > 1 ? 's' : '');
      return 'hace ' + Math.floor(h / 24) + ' dia(s)';
    },

    async trackVista(id) {
      const fd = new FormData();
      fd.append('action', 'track');
      fd.append('tipo', 'vista_producto');
      fd.append('referencia', id);
      fetch('/api/tracking.php', { method: 'POST', body: fd });
    },

    // ─── CROSS-SELLING ───────────────────────────────────
    async cargarRelacionados(id) {
      const cats = this.productoDetalle?.categorias?.[0]?.slug || '';
      if (!cats) return;
      const r = await fetch('/api/productos.php?action=list&tipo=tienda&categoria=' + cats + '&page=1');
      const d = await r.json();
      this.productosRelacionados = (d.productos || []).filter(p => p.id != id).slice(0, 4);
    },

    // ─── CARRITO ABANDONADO ──────────────────────────────
    async guardarCarritoAbandonado() {
      if (!this.usuario || this.cart.length === 0) return;
      const fd = new FormData();
      fd.append('action', 'carrito_save');
      fd.append('email', this.usuario.email || '');
      fd.append('items', JSON.stringify(this.cart));
      fd.append('total', this.cartTotal());
      fetch('/api/tracking.php', { method: 'POST', body: fd });
    },

    async recuperarCarrito() {
      if (this.cart.length > 0) return; // ya tiene items
      const r = await fetch('/api/tracking.php?action=carrito_get');
      const d = await r.json();
      if (d.items && d.items.length > 0) {
        this.cart = d.items;
        this.saveCart();
        this.showToast('Recuperamos tu carrito anterior');
      }
    },

    // ─── POPUP ───────────────────────────────────────────
    initPopup() {
      if (localStorage.getItem('popup_cerrado')) return;
      if (this.config.popup_activo !== '1') return;
      document.addEventListener('mouseleave', (e) => {
        if (e.clientY <= 0 && !this.popupCerrado && !this.popupVisible) {
          setTimeout(() => { this.popupVisible = true; }, 500);
        }
      });
      setTimeout(() => {
        if (!this.popupCerrado && !this.popupVisible) this.popupVisible = true;
      }, 30000);
    },

    async suscribirPopup() {
      const fd = new FormData();
      fd.append('action', 'newsletter');
      fd.append('email', this.popupEmail);
      await fetch('/api/pedidos.php', { method: 'POST', body: fd });
      this.popupVisible = false;
      this.popupCerrado = true;
      localStorage.setItem('popup_cerrado', '1');
      this.showToast('Cupon BIENVENIDO10 copiado! Usalo en tu compra');
    },

    // ─── CUPON ───────────────────────────────────────────
    async aplicarCupon(codigo, total, onOk, onError) {
      if (!codigo) return;
      const fd = new FormData();
      fd.append('action', 'cupon_validar');
      fd.append('codigo', codigo);
      fd.append('total', total);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.error) onError(d.error);
      else { this.checkout.descuento = d.descuento; onOk(d); }
    },

    // ─── AUTH ────────────────────────────────────────────
    async checkSesion() {
      const r = await fetch('/api/auth.php?action=perfil');
      const d = await r.json();
      if (d.logueado) {
        this.usuario = d.usuario;
        this.perfil  = d;
        this.checkout.nombre   = d.usuario.nombre + ' ' + d.usuario.apellido;
        this.checkout.email    = d.usuario.email;
        this.checkout.telefono = d.usuario.telefono || '';
      }
    },

    async login() {
      this.loginLoading = true; this.loginError = '';
      const fd = new FormData();
      fd.append('action', 'login');
      fd.append('email', this.loginForm.email);
      fd.append('password', this.loginForm.password);
      const r = await fetch('/api/auth.php', { method: 'POST', body: fd });
      const d = await r.json();
      this.loginLoading = false;
      if (d.error) { this.loginError = d.error; return; }
      await this.checkSesion();
      this.page = 'home';
      this.showToast('Bienvenido, ' + d.nombre + '!');
    },

    async registro() {
      this.regLoading = true; this.regError = '';
      const fd = new FormData();
      fd.append('action', 'registro');
      Object.entries(this.regForm).forEach(([k, v]) => fd.append(k, v));
      const r = await fetch('/api/auth.php', { method: 'POST', body: fd });
      const d = await r.json();
      this.regLoading = false;
      if (d.error) { this.regError = d.error; return; }
      await this.checkSesion();
      this.page = 'home';
      this.showToast('Cuenta creada! Bienvenido');
    },

    async logout() {
      const fd = new FormData();
      fd.append('action', 'logout');
      await fetch('/api/auth.php', { method: 'POST', body: fd });
      this.usuario = null; this.perfil = null;
      this.page = 'home';
      this.showToast('Sesion cerrada');
    },

    async guardarPerfil() {
      const fd = new FormData();
      fd.append('action', 'update');
      fd.append('nombre',   this.perfil.usuario.nombre);
      fd.append('apellido', this.perfil.usuario.apellido);
      fd.append('telefono', this.perfil.usuario.telefono || '');
      if (this.nuevaPassword) fd.append('password', this.nuevaPassword);
      const r = await fetch('/api/auth.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) { this.nuevaPassword = ''; this.showToast('Perfil actualizado'); }
    },

    // ─── DIRECCIONES ────────────────────────────────────────
    abrirFormDireccion(dir) {
      this.formDireccion = dir
        ? { ...dir, predeterminada: !!dir.predeterminada }
        : { id: null, alias: '', calle: '', numero: '', depto: '', ciudad: 'La Serena', region: 'Coquimbo', predeterminada: false };
    },

    async guardarDireccion() {
      const fd = new FormData();
      fd.append('action', 'guardar');
      ['id','alias','calle','numero','depto','ciudad','region'].forEach(k => fd.append(k, this.formDireccion[k] ?? ''));
      fd.append('predeterminada', this.formDireccion.predeterminada ? 1 : 0);
      const r = await fetch('/api/direcciones.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.formDireccion = null;
        await this.checkSesion();
        this.showToast('Dirección guardada');
      }
    },

    async eliminarDireccion(id) {
      if (!confirm('Eliminar esta dirección?')) return;
      const fd = new FormData();
      fd.append('action', 'eliminar');
      fd.append('id', id);
      await fetch('/api/direcciones.php', { method: 'POST', body: fd });
      await this.checkSesion();
      this.showToast('Dirección eliminada');
    },

    async setPredeterminada(id) {
      const fd = new FormData();
      fd.append('action', 'predeterminada');
      fd.append('id', id);
      await fetch('/api/direcciones.php', { method: 'POST', body: fd });
      await this.checkSesion();
      this.showToast('Dirección predeterminada actualizada');
    },

    async verDetallePedido(id) {
      const r = await fetch('/api/pedidos.php?action=detalle&id=' + id);
      this.pedidoClienteDetalle = await r.json();
    },

    async realizarPedido() {
      // Validacion basica
      if (!this.checkout.nombre || !this.checkout.email) {
        this.checkoutError = 'Nombre y email son requeridos'; return;
      }
      if (this.checkout.metodo_entrega === 'delivery' && (!this.checkout.direccion || !this.checkout.ciudad)) {
        this.checkoutError = 'Dirección y ciudad son requeridas para delivery'; return;
      }
      if (!this.checkout.metodo_pago) {
        this.checkoutError = 'Selecciona un método de pago'; return;
      }

      // Descuento primer pedido
      let descuentoPrimerPedido = 0;
      if (this.config.descuento_primer_pedido === '1' && this.usuario) {
        const r = await fetch('/api/pedidos.php?action=mis');
        const pedidos = await r.json();
        if (!pedidos.error && pedidos.length === 0) {
          descuentoPrimerPedido = Math.round(this.cartTotal() * 0.10);
        }
      }

      const totalDescuento = this.checkout.descuento || 0;
      const totalFinal = Math.max(0, this.totalConDescuento() - descuentoPrimerPedido);

      // Aviso horario
      const hora = new Date().getHours();
      const despachoMsg = hora >= 20
        ? 'Pedido recibido despues de las 20:00 hrs. Sera despachado manana por la manana.'
        : 'Tu pedido sera despachado hoy.';

      // Armar productos
      const productos = this.cart.map(i => `- ${i.nombre} x${i.cantidad} -> ${this.formatPrecio(i.precio * i.cantidad)}`).join('\n');

      // Entrega
      let entregaMsg = '';
      if (this.checkout.metodo_entrega === 'retiro') {
        const sucursales = { Balmaceda: 'Av. Balmaceda 4521 Local #2, La Serena', Geronimo: 'Geronimo Mendez, Coquimbo', Alessandri: 'Alessandri 147, El Llano' };
        entregaMsg = `Retiro en tienda: ${sucursales[this.checkout.sucursal_retiro] || this.checkout.sucursal_retiro}`;
      } else {
        const zona = this.zonas.find(z => z.id == this.checkout.zona_id);
        entregaMsg = `Delivery a: ${this.checkout.direccion}, ${this.checkout.ciudad}`;
        if (zona) entregaMsg += ` (${zona.nombre})`;
      }

      // Descuentos
      let descuentosMsg = '';
      if (totalDescuento > 0) descuentosMsg += `\nCupon: -${this.formatPrecio(totalDescuento)}`;
      if (descuentoPrimerPedido > 0) descuentosMsg += `\nDescuento primer pedido (10%): -${this.formatPrecio(descuentoPrimerPedido)}`;

      const msg = encodeURIComponent(
        `*Nuevo Pedido - Mascotiendas*\n\n`
        + `*Cliente:* ${this.checkout.nombre}\n`
        + `*Email:* ${this.checkout.email}\n`
        + `*Telefono:* ${this.checkout.telefono || 'No indicado'}\n\n`
        + `*Productos:*\n${productos}\n\n`
        + `${entregaMsg}\n`
        + `*Pago:* ${this.checkout.metodo_pago}${descuentosMsg}\n`
        + (this.checkout.horario ? `*Horario preferido:* ${this.checkout.horario}\n` : '')
        + `*Total: ${this.formatPrecio(totalFinal)}*\n\n`
        + (this.checkout.notas ? `*Notas:* ${this.checkout.notas}\n\n` : '')
        + despachoMsg
      );

      window.open(`https://wa.me/56974851872?text=${msg}`, '_blank');

      // Limpiar carrito
      this.cart = []; this.saveCart();
      this.checkout.descuento = 0;
      this.page = 'home';
      this.showToast('✅ Redirigiendo a WhatsApp para confirmar tu pedido');
    },

    async suscribir() {
      const fd = new FormData();
      fd.append('action', 'newsletter');
      fd.append('email', this.newsletterEmail);
      await fetch('/api/pedidos.php', { method: 'POST', body: fd });
      this.newsletterEmail = '';
      this.showToast('Suscrito! Gracias');
    },

    // ─── FAVORITOS ───────────────────────────────────────
    async cargarFavoritos() {
      const r = await fetch('/api/favoritos.php?action=lista');
      this.favoritos = await r.json();
    },

    async toggleFavorito(productoId) {
      if (!this.usuario) { this.page = 'login'; return; }
      const fd = new FormData();
      fd.append('action', 'toggle');
      fd.append('producto_id', productoId);
      const r = await fetch('/api/favoritos.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        await this.cargarFavoritos();
        this.showToast(d.favorito ? 'Agregado a favoritos' : 'Eliminado de favoritos');
      }
    },

    esFavorito(id) { return this.favoritos.some(f => f.id == id); },

    async avisarStock(productoId, email) {
      const fd = new FormData();
      fd.append('action', 'aviso_stock');
      fd.append('producto_id', productoId);
      fd.append('email', email);
      await fetch('/api/favoritos.php', { method: 'POST', body: fd });
      this.showToast('Te avisaremos cuando llegue al stock');
    },

    // ─── BLOG ────────────────────────────────────────────
    async cargarBlog() {
      this.blogPost = null;
      const r = await fetch('/api/contenido.php?action=lista');
      this.blogPosts = await r.json();
    },

    async abrirPost(slug) {
      const r = await fetch('/api/contenido.php?action=detalle&slug=' + slug);
      this.blogPost = await r.json();
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
    },

    // ─── PAGINAS ESTATICAS ───────────────────────────────
    async abrirPagina(slug, push = true) {
      this.page           = 'pagina';
      this.cargandoPagina = true;
      this.paginaEstatica = null;
      if (push) this.pushURL('pagina', { slug });
      const r = await fetch('/api/contenido.php?action=pagina&slug=' + slug);
      this.paginaEstatica = await r.json();
      this.cargandoPagina = false;
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
    },

    // ─── ZONAS DELIVERY ──────────────────────────────────
    async cargarZonas() {
      if (this.zonas.length) return;
      const r = await fetch('/api/contenido.php?action=zonas');
      this.zonas = await r.json();
    },

    // ─── CONFIGURACION ──────────────────────────────────
    async cargarConfig() {
      const r = await fetch('/api/contenido.php?action=config');
      const d = await r.json();
      if (!d.error) this.config = d;
    },

    async cargarTextos() {
      const r = await fetch('/api/contenido.php?action=textos');
      const d = await r.json();
      if (!d.error) this.textos = d;
    },

    async cargarCampanaActiva() {
      const r = await fetch('/api/contenido.php?action=campana_activa');
      const d = await r.json();
      this.campanaActiva = d;
      if (d) this.initCampana(d);
    },

    initCampana(c) {
      if (localStorage.getItem('campana_cerrada_' + c.id)) return;
      const mostrar = () => { this.popupVisible = true; this.campanaActiva = c; };
      if (c.activacion === 'entrada') { setTimeout(mostrar, 500); }
      else if (c.activacion === 'segundos') { setTimeout(mostrar, (parseInt(c.segundos) || 5) * 1000); }
      else {
        document.addEventListener('mouseleave', (e) => {
          if (e.clientY <= 0 && !this.popupVisible) setTimeout(mostrar, 500);
        }, { once: true });
      }
    },
  };
}
