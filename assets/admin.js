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

    async init() {
      await this.cargarStats();
      await this.cargarBajoStock();
      await this.cargarConfig();
      setInterval(() => this.cargarStats(), 60000);
    },

    formatPrecio(n) { return '$' + Number(n).toLocaleString('es-CL'); },

    showToast(msg) {
      this.toast = msg;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => this.toast = '', 2500);
    },

    // ─── STATS ───────────────────────────────────────────
    async cargarStats() {
      const r = await fetch('/api/admin.php?action=stats');
      this.stats = await r.json();
    },

    async cargarBajoStock() {
      const r = await fetch('/api/admin.php?action=bajo_stock');
      this.bajoStockList = await r.json();
    },

    // ─── PRODUCTOS ───────────────────────────────────────
    async cargarProductos() {
      const params = new URLSearchParams({
        action: 'productos_list',
        page: this.prodPagina,
        limit: 50,
        q: this.prodQ,
        stock: this.prodFiltroStock,
        orden: this.prodOrden
      });
      const r = await fetch('/api/admin.php?' + params);
      const d = await r.json();
      this.listaProductos = d.productos;
      this.prodPaginas    = d.paginas || 1;
    },

    abrirFormProducto(p) {
      this.formProducto = p
        ? { ...p, imagenes: [], en_stock: !!p.en_stock, activo: !!p.activo }
        : { id: null, nombre: '', precio_normal: '', precio_rebajado: '', sku: '',
            descripcion_corta: '', descripcion: '', en_stock: true, activo: true,
            inventario_actual: 0, stock_minimo: 5, imagenes: [] };
      if (p?.id) {
        this.cargarImagenesProducto(p.id);
        this.cargarVariantesProducto(p.id);
      }
      if (!this.atributos.length) this.cargarAtributos();
    },

    async cargarImagenesProducto(id) {
      const r = await fetch('/api/productos.php?action=detalle&id=' + id);
      const d = await r.json();
      if (this.formProducto) this.formProducto.imagenes = d.imagenes || [];
    },

    async guardarProducto() {
      const fd = new FormData();
      fd.append('action', 'producto_save');
      ['id','nombre','precio_normal','precio_rebajado','sku','descripcion_corta','descripcion','inventario_actual','stock_minimo']
        .forEach(c => fd.append(c, this.formProducto[c] ?? ''));
      fd.append('en_stock', this.formProducto.en_stock ? 1 : 0);
      fd.append('activo',   this.formProducto.activo   ? 1 : 0);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        if (!this.formProducto.id) this.formProducto.id = d.id;
        this.showToast('Producto guardado');
        this.cargarProductos();
        this.cargarStats();
      }
    },

    async eliminarProducto(id) {
      if (!confirm('Eliminar este producto permanentemente?')) return;
      const fd = new FormData();
      fd.append('action', 'producto_delete');
      fd.append('id', id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.showToast('Producto eliminado');
      this.formProducto = null;
      this.cargarProductos();
      this.cargarStats();
    },

    async subirImagen(event) {
      const file = event.target.files[0];
      if (!file || !this.formProducto?.id) return;
      const fd = new FormData();
      fd.append('action', 'imagen_upload');
      fd.append('producto_id', this.formProducto.id);
      fd.append('imagen', file);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        await this.cargarImagenesProducto(this.formProducto.id);
        this.showToast('Imagen subida');
      } else {
        this.showToast('Error: ' + d.error);
      }
      event.target.value = '';
    },

    async eliminarImagen(imgId) {
      if (!confirm('Eliminar esta imagen?')) return;
      const fd = new FormData();
      fd.append('action', 'imagen_delete');
      fd.append('imagen_id', imgId);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        await this.cargarImagenesProducto(this.formProducto.id);
        this.showToast('Imagen eliminada');
      }
    },

    // ─── VARIANTES ───────────────────────────────────────────
    async cargarAtributos() {
      const r = await fetch('/api/admin.php?action=atributos_list');
      this.atributos = await r.json();
    },

    async cargarVariantesProducto(id) {
      const r = await fetch('/api/admin.php?action=variantes_list&producto_id=' + id);
      const d = await r.json();
      if (this.formProducto) this.formProducto.variantes = d;
    },

    abrirFormVariante(v) {
      this.formVariante = v
        ? { ...v, en_stock: !!v.en_stock, atributo_valores: [] }
        : { id: null, producto_id: this.formProducto.id, sku: '', precio_normal: '',
            precio_rebajado: '', stock: 0, en_stock: true, imagen_url: '', atributo_valores: [] };
    },

    toggleAtribVal(id) {
      const idx = this.formVariante.atributo_valores.indexOf(id);
      if (idx >= 0) this.formVariante.atributo_valores.splice(idx, 1);
      else this.formVariante.atributo_valores.push(id);
    },

    async guardarVariante() {
      const fd = new FormData();
      fd.append('action', 'variante_save');
      ['id','producto_id','sku','precio_normal','precio_rebajado','stock','imagen_url']
        .forEach(k => fd.append(k, this.formVariante[k] ?? ''));
      fd.append('en_stock', this.formVariante.en_stock ? 1 : 0);
      fd.append('atributo_valores', JSON.stringify(this.formVariante.atributo_valores));
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.formVariante = null;
        await this.cargarVariantesProducto(this.formProducto.id);
        this.showToast('Variante guardada');
      }
    },

    async eliminarVariante(id) {
      if (!confirm('Eliminar esta variante?')) return;
      const fd = new FormData();
      fd.append('action', 'variante_delete');
      fd.append('id', id);
      fd.append('producto_id', this.formProducto.id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      await this.cargarVariantesProducto(this.formProducto.id);
      this.showToast('Variante eliminada');
    },

    // ─── PEDIDOS ─────────────────────────────────────────
    async cargarPedidos() {
      const params = new URLSearchParams({
        action: 'pedidos_list',
        page: this.pedidoPagina,
        estado: this.pedidoFiltro,
        q: this.pedidoQ
      });
      const r = await fetch('/api/admin.php?' + params);
      const d = await r.json();
      this.listaPedidos   = d.pedidos;
      this.pedidoPaginas  = d.paginas || 1;
      this.totalPedidos   = d.total || 0;
    },

    async cambiarEstado(id, estado) {
      const fd = new FormData();
      fd.append('action', 'pedido_estado');
      fd.append('id', id);
      fd.append('estado', estado);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.showToast('Estado actualizado');
      this.cargarStats();
    },

    async verPedido(id) {
      const r = await fetch('/api/admin.php?action=pedido_detalle&id=' + id);
      this.pedidoDetalle = await r.json();
    },

    // ─── USUARIOS ────────────────────────────────────────
    async cargarUsuarios() {
      const params = new URLSearchParams({ action: 'usuarios_list', q: this.usuarioQ });
      const r = await fetch('/api/admin.php?' + params);
      this.listaUsuarios = await r.json();
    },

    // ─── CUPONES ─────────────────────────────────────────
    async cargarCupones() {
      const r = await fetch('/api/admin.php?action=cupones_list');
      this.listaCupones = await r.json();
    },

    abrirFormCupon(c) {
      this.formCupon = c
        ? { ...c, activo: !!c.activo }
        : { id: null, codigo: '', tipo: 'porcentaje', valor: '', minimo_compra: 0, usos_max: '', expira_en: '', activo: true };
    },

    async guardarCupon() {
      const fd = new FormData();
      fd.append('action', 'cupon_save');
      ['id','codigo','tipo','valor','minimo_compra','usos_max','expira_en']
        .forEach(k => fd.append(k, this.formCupon[k] ?? ''));
      fd.append('activo', this.formCupon.activo ? 1 : 0);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) { this.formCupon = null; this.cargarCupones(); this.showToast('Cupon guardado'); }
      else this.showToast('Error: ' + d.error);
    },

    async eliminarCupon(id) {
      if (!confirm('Eliminar este cupon?')) return;
      const fd = new FormData();
      fd.append('action', 'cupon_delete');
      fd.append('id', id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.cargarCupones();
      this.showToast('Cupon eliminado');
    },

    // ─── BLOG ────────────────────────────────────────────
    async cargarBlogAdmin() {
      const r = await fetch('/api/admin.php?action=blog_list');
      this.listaBlog = await r.json();
    },

    abrirFormPost(p) {
      this.formPost = p
        ? { ...p, publicado: !!p.publicado }
        : { id: null, titulo: '', slug: '', extracto: '', contenido: '', imagen_portada: '', meta_titulo: '', meta_descripcion: '', publicado: false };
    },

    slugify(texto) {
      return texto.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
    },

    async guardarPost() {
      const fd = new FormData();
      fd.append('action', 'blog_save');
      ['id','titulo','slug','extracto','contenido','imagen_portada','meta_titulo','meta_descripcion']
        .forEach(k => fd.append(k, this.formPost[k] ?? ''));
      fd.append('publicado', this.formPost.publicado ? 1 : 0);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.formPost = null;
        this.cargarBlogAdmin();
        this.showToast('Post guardado');
      } else {
        this.showToast('Error: ' + (d.error || 'Error al guardar'));
      }
    },

    async eliminarPost(id) {
      if (!confirm('Eliminar este post?')) return;
      const fd = new FormData();
      fd.append('action', 'blog_delete');
      fd.append('id', id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.cargarBlogAdmin();
      this.showToast('Post eliminado');
    },

    // ─── REVIEWS ─────────────────────────────────────────
    async cargarReviewsAdmin() {
      const params = new URLSearchParams({ action: 'reviews_list' });
      if (this.reviewFiltro >= 0) params.set('aprobado', this.reviewFiltro);
      const r = await fetch('/api/admin.php?' + params);
      this.listaReviews = await r.json();
    },

    async aprobarReview(id, val) {
      const fd = new FormData();
      fd.append('action', 'review_aprobar');
      fd.append('id', id);
      fd.append('aprobado', val);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.cargarReviewsAdmin();
      this.showToast(val ? 'Reseña aprobada' : 'Reseña ocultada');
    },

    async eliminarReview(id) {
      if (!confirm('Eliminar esta reseña?')) return;
      const fd = new FormData();
      fd.append('action', 'review_delete');
      fd.append('id', id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.cargarReviewsAdmin();
      this.showToast('Reseña eliminada');
    },

    // ─── MARKETING ───────────────────────────────────────
    async cargarNewsletter() {
      const r = await fetch('/api/admin.php?action=newsletter_list');
      this.listaNewsletter = await r.json();
    },

    exportarNewsletter() {
      const csv = 'Email,Fecha\n' + this.listaNewsletter.map(s => `${s.email},${s.creado_en}`).join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'newsletter_' + new Date().toISOString().slice(0,10) + '.csv';
      a.click();
    },

    // ─── NOTIFICACIONES ──────────────────────────────────
    async cargarNotif() {
      const r = await fetch('/api/admin.php?action=notif_list');
      this.listaNotif = await r.json();
    },

    async leerNotif(n) {
      if (n.leida) return;
      const fd = new FormData();
      fd.append('action', 'notif_leer');
      fd.append('id', n.id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      n.leida = true;
      this.stats.no_leidas = Math.max(0, (this.stats.no_leidas || 1) - 1);
    },

    async leerTodasNotif() {
      const fd = new FormData();
      fd.append('action', 'notif_leer_todas');
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.listaNotif.forEach(n => n.leida = true);
      this.stats.no_leidas = 0;
      this.showToast('Todas marcadas como leidas');
    },

    // ─── CONFIGURACION ──────────────────────────────────
    async cargarConfig() {
      const r = await fetch('/api/admin.php?action=config_get');
      const d = await r.json();
      if (!d.error) this.config = d;
    },

    async toggleDescuentoPrimerPedido() {
      const nuevoValor = this.config.descuento_primer_pedido === '1' ? '0' : '1';
      const fd = new FormData();
      fd.append('action', 'config_save');
      fd.append('clave', 'descuento_primer_pedido');
      fd.append('valor', nuevoValor);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.config.descuento_primer_pedido = nuevoValor;
      this.showToast(nuevoValor === '1' ? 'Descuento primer pedido activado' : 'Descuento primer pedido desactivado');
    },

    async toggleConfig(clave) {
      const nuevoValor = this.config[clave] === '1' ? '0' : '1';
      await this.guardarConfig(clave, nuevoValor);
      this.config[clave] = nuevoValor;
      this.showToast(nuevoValor === '1' ? 'Activado' : 'Desactivado');
    },

    async guardarConfig(clave, valor) {
      const fd = new FormData();
      fd.append('action', 'config_save');
      fd.append('clave', clave);
      fd.append('valor', valor);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
    },

    // ─── TEXTOS MAGICOS ─────────────────────────────────
    async cargarTextos() {
      const r = await fetch('/api/admin.php?action=textos_get');
      const d = await r.json();
      if (!d.error) this.textos = d;
    },

    async guardarTexto(clave, valor) {
      const fd = new FormData();
      fd.append('action', 'texto_save');
      fd.append('clave', clave);
      fd.append('valor', valor);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.showToast('Guardado');
    },

    // ─── CAMPANAS ───────────────────────────────────────
    async cargarCampanas() {
      const r = await fetch('/api/admin.php?action=campanas_list');
      this.listaCampanas = await r.json();
    },

    abrirFormCampana(c) {
      this.formCampana = c
        ? { ...c, activo: !!parseInt(c.activo) }
        : { id: null, nombre: '', titulo: '', texto: '', imagen_url: '', btn_texto: '', btn_url: '', activacion: 'exit', segundos: 5, fecha_ini: '', fecha_fin: '', activo: true };
    },

    async guardarCampana() {
      const fd = new FormData();
      fd.append('action', 'campana_save');
      ['id','nombre','titulo','texto','imagen_url','btn_texto','btn_url','activacion','segundos','fecha_ini','fecha_fin']
        .forEach(k => fd.append(k, this.formCampana[k] ?? ''));
      fd.append('activo', this.formCampana.activo ? 1 : 0);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) { this.formCampana = null; this.cargarCampanas(); this.showToast('Campaña guardada'); }
      else this.showToast('Error: ' + d.error);
    },

    async eliminarCampana(id) {
      if (!confirm('Eliminar esta campaña?')) return;
      const fd = new FormData();
      fd.append('action', 'campana_delete');
      fd.append('id', id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      this.cargarCampanas();
      this.showToast('Campaña eliminada');
    },

    async toggleCampana(c) {
      const fd = new FormData();
      fd.append('action', 'campana_toggle');
      fd.append('id', c.id);
      await fetch('/api/admin.php', { method: 'POST', body: fd });
      c.activo = c.activo == '1' ? '0' : '1';
      this.showToast(c.activo == '1' ? 'Campaña activada' : 'Campaña desactivada');
    }
  };
}
