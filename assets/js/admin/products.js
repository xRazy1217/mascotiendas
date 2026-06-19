// Submódulo de Gestión de Productos y Variantes para el Panel de Administración

export function initProducts() {
  return {
    selectedProducts: [],

    async cargarProductos() {
      this.selectedProducts = [];
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
        ? { ...p, imagenes: [], en_stock: !!p.en_stock, activo: !!p.activo, categorias: [] }
        : { id: null, nombre: '', precio_normal: '', precio_rebajado: '', sku: '',
            descripcion_corta: '', descripcion: '', en_stock: true, activo: true,
            inventario_actual: 0, stock_minimo: 5, imagenes: [], categorias: [] };
      if (p?.id) {
        this.cargarImagenesProducto(p.id);
        this.cargarVariantesProducto(p.id);
      }
      if (!this.atributos.length) this.cargarAtributos();
      if (!this.listaCategorias.length) this.cargarCategoriasAdmin();
    },

    async cargarImagenesProducto(id) {
      const r = await fetch('/api/productos.php?action=detalle&id=' + id);
      const d = await r.json();
      if (this.formProducto) {
        this.formProducto.imagenes = d.imagenes || [];
        this.formProducto.categorias = (d.categorias || []).map(c => parseInt(c.id));
      }
    },

    async guardarProducto() {
      const fd = new FormData();
      fd.append('action', 'producto_save');
      ['id','nombre','precio_normal','precio_rebajado','sku','descripcion_corta','descripcion','inventario_actual','stock_minimo']
        .forEach(c => fd.append(c, this.formProducto[c] ?? ''));
      fd.append('en_stock', this.formProducto.en_stock ? 1 : 0);
      fd.append('activo',   this.formProducto.activo   ? 1 : 0);
      fd.append('categorias', JSON.stringify(this.formProducto.categorias || []));
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

    abrirModalRecorte(img) {
      this.modalRecorte = img;
      this.recorteTab = 'catalogo';
      
      let existingConfig = {};
      if (img.crop_config) {
        try {
          existingConfig = typeof img.crop_config === 'string' ? JSON.parse(img.crop_config) : img.crop_config;
        } catch(e) {}
      }
      
      const defaults = {
        catalogo: { fit: 'contain', x: 50, y: 50, zoom: 100 },
        detalle: { fit: 'contain', x: 50, y: 50, zoom: 100 },
        miniatura: { fit: 'cover', x: 50, y: 50, zoom: 100 }
      };
      
      this.recorteConfig = {
        catalogo: { ...defaults.catalogo, ...(existingConfig.catalogo || {}) },
        detalle: { ...defaults.detalle, ...(existingConfig.detalle || {}) },
        miniatura: { ...defaults.miniatura, ...(existingConfig.miniatura || {}) }
      };
    },

    obtenerEstiloPrevisualizacion() {
      if (!this.recorteConfig || !this.recorteTab) return '';
      const view = this.recorteConfig[this.recorteTab];
      const fit = view.fit || 'contain';
      const x = view.x !== undefined ? view.x : 50;
      const y = view.y !== undefined ? view.y : 50;
      const zoom = view.zoom !== undefined ? view.zoom : 100;
      return `object-fit: ${fit}; object-position: ${x}% ${y}%; transform: scale(${zoom / 100});`;
    },

    restablecerRecorte() {
      if (!this.recorteConfig || !this.recorteTab) return;
      const defaults = {
        catalogo: { fit: 'contain', x: 50, y: 50, zoom: 100 },
        detalle: { fit: 'contain', x: 50, y: 50, zoom: 100 },
        miniatura: { fit: 'cover', x: 50, y: 50, zoom: 100 }
      };
      this.recorteConfig[this.recorteTab] = { ...defaults[this.recorteTab] };
    },

    async guardarRecorte() {
      if (!this.modalRecorte || !this.recorteConfig) return;
      const fd = new FormData();
      fd.append('action', 'imagen_crop_save');
      fd.append('imagen_id', this.modalRecorte.id);
      fd.append('crop_config', JSON.stringify(this.recorteConfig));
      
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast('Configuración de recorte guardada');
        await this.cargarImagenesProducto(this.formProducto.id);
        this.modalRecorte = null;
      } else {
        this.showToast('Error al guardar: ' + d.error);
      }
    },

    // Atributos y Variantes
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

    toggleProductSelection(id) {
      const idx = this.selectedProducts.indexOf(id);
      if (idx >= 0) {
        this.selectedProducts = this.selectedProducts.filter(x => x !== id);
      } else {
        this.selectedProducts = [...this.selectedProducts, id];
      }
    },

    selectAllProducts(event) {
      if (event.target.checked) {
        this.selectedProducts = this.listaProductos.map(p => p.id);
      } else {
        this.selectedProducts = [];
      }
    },

    async bulkStockAction(tipo, val = null) {
      if (this.selectedProducts.length === 0) return;
      
      let msg = '';
      if (tipo === 'stock_on') msg = '¿Marcar como CON STOCK los productos seleccionados?';
      if (tipo === 'stock_off') msg = '¿Marcar como SIN STOCK los productos seleccionados?';
      if (tipo === 'inventario_set') {
        const inputVal = prompt('Ingrese el nuevo valor de inventario para los productos seleccionados:', '0');
        if (inputVal === null) return;
        val = parseInt(inputVal, 10);
        if (isNaN(val) || val < 0) {
          alert('Valor de inventario no válido.');
          return;
        }
        msg = `¿Actualizar el inventario a ${val} para los productos seleccionados?`;
      }

      if (!confirm(msg)) return;

      const fd = new FormData();
      fd.append('action', 'productos_accion_masiva');
      fd.append('ids', JSON.stringify(this.selectedProducts));
      fd.append('tipo_accion', tipo);
      if (val !== null) {
        fd.append('valor', val);
      }

      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast(`${d.updated_count} productos actualizados.`);
        this.selectedProducts = [];
        this.cargarProductos();
        this.cargarStats();
      } else {
        this.showToast('Error: ' + d.error);
      }
    }
  };
}
