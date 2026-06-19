// Submódulo de Catálogo de Productos y Reviews para el Cliente

export function initProducts() {
  return {
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
      // GA4: view_item_list
      const listName = tipo === 'farmacia' ? 'Farmacia' : (this.filtroCategoria || 'Catálogo');
      this.trackViewItemList(d.productos, listName);
      // GA4: search
      if (this.filtroQ) this.trackSearch(this.filtroQ);
    },

    async abrirProducto(id, push = true, slug = '') {
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
      if (push) this.pushURL('producto', { id, slug });
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
      if (push && !slug) {
        const freshSlug = d.slug || this.slugify(d.nombre);
        const base = window.MT_BASE_PATH || '';
        history.replaceState({}, '', base + '/producto/' + id + '-' + freshSlug);
      }
      this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' }));
      
      // Cargar datos adicionales en paralelo
      this.cargarReviews(id);
      this.cargarFomo(id);
      this.cargarRelacionados(id);
      this.trackVista(id);
      // GA4: Enhanced Ecommerce view_item
      this.trackViewItem(d);
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

    esFavorito(id) { 
      return this.favoritos.some(f => f.id == id); 
    },

    async avisarStock(productoId, email) {
      const fd = new FormData();
      fd.append('action', 'aviso_stock');
      fd.append('producto_id', productoId);
      fd.append('email', email);
      await fetch('/api/favoritos.php', { method: 'POST', body: fd });
      this.showToast('Te avisaremos cuando llegue al stock');
    }
  };
}
