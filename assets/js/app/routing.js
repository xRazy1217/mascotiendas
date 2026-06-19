// Submódulo de Enrutador (Routing), Blog, Páginas Estáticas y Popups para el Cliente

export function initRouting() {
  return {
    getCurrentPageFromURL() {
      // 1. Check query param fallback
      const params = new URLSearchParams(window.location.search);
      const queryPage = params.get('p');
      if (queryPage) return queryPage;

      // 2. Otherwise parse pathname
      let relPath = window.location.pathname;
      const base = window.MT_BASE_PATH || '';
      if (base && relPath.indexOf(base) === 0) {
        relPath = relPath.substring(base.length);
      }
      relPath = relPath.replace(/^\/+|\/+$/g, '');

      if (!relPath || relPath === '') return 'home';
      if (relPath.indexOf('producto/') === 0) return 'producto';
      if (relPath.indexOf('blog/') === 0) return 'blog';
      if (relPath.indexOf('pagina/') === 0) return 'pagina';
      
      const simplePages = ['tienda','farmacia','comunidad','blog','carrito','checkout','login','registro','perfil','favoritos'];
      if (simplePages.includes(relPath)) return relPath;
      
      return 'home';
    },

    handleURL() {
      let relPath = window.location.pathname;
      const base = window.MT_BASE_PATH || '';
      if (base && relPath.indexOf(base) === 0) {
        relPath = relPath.substring(base.length);
      }
      relPath = relPath.replace(/^\/+|\/+$/g, '');

      const params = new URLSearchParams(window.location.search);
      const queryPage = params.get('p');
      if (queryPage) {
        const id = params.get('id');
        const slug = params.get('slug');
        if (queryPage === 'producto' && id) {
          this.abrirProducto(id, false, slug);
        } else if (queryPage === 'blog' && slug) {
          this.abrirPost(slug, false);
        } else if (queryPage === 'pagina' && slug) {
          this.abrirPagina(slug, false);
        } else {
          const pages = ['home','tienda','farmacia','comunidad','carrito','checkout','login','registro','perfil','favoritos','blog'];
          this.page = pages.includes(queryPage) ? queryPage : 'home';
        }
        return;
      }

      if (!relPath || relPath === '') {
        this.page = 'home';
      } else if (relPath.indexOf('producto/') === 0) {
        const parts = relPath.substring(9).split('-');
        const id = parts[0];
        const slug = parts.slice(1).join('-');
        if (id) {
          this.abrirProducto(id, false, slug);
        } else {
          this.page = 'home';
        }
      } else if (relPath.indexOf('blog/') === 0) {
        const slug = relPath.substring(5);
        if (slug) {
          this.abrirPost(slug, false);
        } else {
          this.page = 'blog';
          this.blogPost = null;
        }
      } else if (relPath.indexOf('pagina/') === 0) {
        const slug = relPath.substring(7);
        if (slug) {
          this.abrirPagina(slug, false);
        } else {
          this.page = 'home';
        }
      } else {
        const pages = ['tienda','farmacia','comunidad','blog','carrito','checkout','login','registro','perfil','favoritos'];
        if (pages.includes(relPath)) {
          this.page = relPath;
          if (relPath === 'blog') {
            this.blogPost = null;
          }
        } else {
          this.page = 'home';
        }
      }
    },

    pushURL(p, extra) {
      const base = window.MT_BASE_PATH || '';
      let path = '/';
      
      if (p === 'home') {
        path = '/';
      } else if (p === 'producto' && extra && extra.id) {
        const slug = extra.slug || this.slugify(this.productoDetalle?.nombre || '');
        path = '/producto/' + extra.id + '-' + slug;
      } else if (p === 'blog' && extra && extra.slug) {
        path = '/blog/' + extra.slug;
      } else if (p === 'pagina' && extra && extra.slug) {
        path = '/pagina/' + extra.slug;
      } else {
        const simplePages = ['tienda','farmacia','comunidad','blog','carrito','checkout','perfil','login','registro','favoritos'];
        if (simplePages.includes(p)) {
          path = '/' + p;
        } else {
          const params = new URLSearchParams({ p });
          if (extra) Object.entries(extra).forEach(([k,v]) => params.set(k, v));
          path = '/?' + params.toString();
        }
      }
      
      history.pushState({}, '', base + path);
    },

    onPageChange(val) {
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
      const actual = this.getCurrentPageFromURL();
      if (actual !== val) {
        const specialPages = ['producto', 'blog', 'pagina'];
        if (!specialPages.includes(val)) {
          this.pushURL(val);
        }
      }
      if (val === 'tienda')    { this.filtroQ = ''; this.filtroCategoria = ''; this.pagina = 1; this.cargarProductos('tienda'); }
      if (val === 'farmacia')  { this.pagina = 1; this.cargarProductos('farmacia'); }
      if (val === 'perfil' && this.usuario) this.checkSesion();
      if (val === 'favoritos') this.cargarFavoritos();
      if (val === 'blog')      this.cargarBlog();
      if (val === 'checkout')  this.cargarZonas();
      if (val === 'comunidad') this.cargarComunidadCliente();
    },

    sucursalNombre() {
      return { Balmaceda: 'Balmaceda 4521', Geronimo: 'Geronimo Mendez', Alessandri: 'Alessandri 147' }[this.sucursal] || this.sucursal;
    },

    getImageStyle(cropConfig, viewType) {
      let config = {};
      if (typeof cropConfig === 'string') {
        try { config = JSON.parse(cropConfig); } catch(e) {}
      } else if (typeof cropConfig === 'object' && cropConfig !== null) {
        config = cropConfig;
      }
      const view = config[viewType] || {};
      const fit = view.fit || (viewType === 'miniatura' ? 'cover' : 'contain');
      const x = view.x !== undefined ? view.x : 50;
      const y = view.y !== undefined ? view.y : 50;
      const zoom = view.zoom !== undefined ? view.zoom : 100;
      const padding = (viewType === 'detalle' && !config.detalle) ? 'padding: 1.5rem;' : '';
      return `object-fit: ${fit}; object-position: ${x}% ${y}%; transform: scale(${zoom / 100}); ${padding}`;
    },

    getActiveImageCrop() {
      if (!this.productoDetalle || !this.productoDetalle.imagenes) return null;
      const img = this.productoDetalle.imagenes.find(i => i.url === this.imagenActiva);
      return img ? img.crop_config : null;
    },

    // ─── POPUP DE NEWSLETTER / CAMPANAS ──────────────────────────────
    initPopup() {
      if (localStorage.getItem('popup_cerrado')) return;
      if (this.config.marketing_automatizacion_activo !== '1' || this.config.popup_activo !== '1') return;
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
      fd.append('action', 'subscribe');
      fd.append('email', this.popupEmail);
      await fetch('/api/newsletter.php', { method: 'POST', body: fd });
      this.popupVisible = false;
      this.popupCerrado = true;
      localStorage.setItem('popup_cerrado', '1');
      this.showToast('Cupon BIENVENIDO10 copiado! Usalo en tu compra');
    },

    // ─── BLOG ────────────────────────────────────────────
    async cargarBlog() {
      this.blogPost = null;
      const r = await fetch('/api/contenido.php?action=lista');
      this.blogPosts = await r.json();
    },

    // ─── COMUNIDAD ────────────────────────────────────────
    async cargarComunidadCliente() {
      this.cargandoComunidadCliente = true;
      try {
        const r = await fetch('/api/contenido.php?action=comunidad');
        this.listaComunidadCliente = await r.json();
      } catch(e) {
        console.error('Error al cargar comunidad:', e);
        this.listaComunidadCliente = [];
      }
      this.cargandoComunidadCliente = false;
    },

    async abrirPost(slug, push = true) {
      this.page     = 'blog';
      this.blogPost = null;
      if (push) this.pushURL('blog', { slug });
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

    async verDetallePedido(id) {
      const r = await fetch('/api/pedidos.php?action=detalle&id=' + id);
      this.pedidoClienteDetalle = await r.json();
    },

    // ─── CONFIGURACION Y CAMPANAS ──────────────────────────────
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
    }
  };
}
