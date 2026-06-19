// Submódulo de Gestión de Categorías para el Panel de Administración

export function initCategories() {
  return {
    listaCategorias: [],
    formCategoria: null,
    cargandoCategorias: false,
    catQ: '',

    async cargarCategoriasAdmin() {
      this.cargandoCategorias = true;
      try {
        const r = await fetch('/api/admin.php?action=categorias_list');
        const d = await r.json();
        this.listaCategorias = Array.isArray(d) ? d : [];
      } catch (e) {
        console.error('Error al cargar categorías:', e);
        this.listaCategorias = [];
      }
      this.cargandoCategorias = false;
    },

    abrirFormCategoria(c) {
      this.formCategoria = c
        ? { ...c, mostrar_home: c.mostrar_home === 1 || c.mostrar_home === '1' }
        : { id: null, nombre: '', slug: '', meta_titulo: '', meta_descripcion: '', imagen_url: '', mostrar_home: false };
    },

    async guardarCategoria() {
      if (!this.formCategoria.nombre) {
        this.showToast('El nombre de la categoría es obligatorio');
        return;
      }

      const fd = new FormData();
      fd.append('action', 'categoria_save');
      ['id', 'nombre', 'slug', 'meta_titulo', 'meta_descripcion', 'imagen_url']
        .forEach(field => fd.append(field, this.formCategoria[field] ?? ''));
      fd.append('mostrar_home', this.formCategoria.mostrar_home ? 1 : 0);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.showToast(this.formCategoria.id ? 'Categoría actualizada' : 'Categoría creada');
          this.formCategoria = null;
          await this.cargarCategoriasAdmin();
          // Actualizar categorías del frontend también si es necesario
          if (typeof this.cargarCategorias === 'function') {
            await this.cargarCategorias();
          }
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error de red al guardar la categoría');
      }
    },

    async eliminarCategoria(id) {
      if (!confirm('¿Eliminar esta categoría permanentemente? Se desvincularán los productos asociados.')) return;
      const fd = new FormData();
      fd.append('action', 'categoria_delete');
      fd.append('id', id);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.showToast('Categoría eliminada');
          await this.cargarCategoriasAdmin();
          if (typeof this.cargarCategorias === 'function') {
            await this.cargarCategorias();
          }
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error de red al eliminar');
      }
    },

    async subirImagenCategoria(event) {
      const file = event.target.files[0];
      if (!file || !this.formCategoria) return;

      const fd = new FormData();
      fd.append('action', 'categoria_imagen_upload');
      fd.append('imagen', file);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.formCategoria.imagen_url = d.url;
          this.showToast('Imagen cargada con éxito');
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error de red al subir la imagen');
      }
      event.target.value = '';
    },

    eliminarImagenCategoria() {
      if (this.formCategoria) {
        this.formCategoria.imagen_url = '';
      }
    },

    async toggleMostrarHome(cat) {
      const nuevoEstado = (cat.mostrar_home === 1 || cat.mostrar_home === '1') ? 0 : 1;
      const fd = new FormData();
      fd.append('action', 'categoria_save');
      fd.append('id', cat.id);
      fd.append('nombre', cat.nombre);
      fd.append('slug', cat.slug);
      fd.append('meta_titulo', cat.meta_titulo ?? '');
      fd.append('meta_descripcion', cat.meta_descripcion ?? '');
      fd.append('imagen_url', cat.imagen_url ?? '');
      fd.append('mostrar_home', nuevoEstado);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          cat.mostrar_home = nuevoEstado;
          this.showToast('Estado actualizado');
          if (typeof this.cargarCategorias === 'function') {
            await this.cargarCategorias();
          }
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error al actualizar visualización en inicio');
      }
    }
  };
}
