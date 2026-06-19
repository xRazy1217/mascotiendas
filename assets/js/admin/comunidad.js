// Submódulo de Gestión de Comunidad para el Panel de Administración

export function initComunidad() {
  return {
    listaComunidad: [],
    formComunidad: null,
    cargandoComunidad: false,
    comQ: '',

    async cargarComunidadAdmin() {
      this.cargandoComunidad = true;
      try {
        const r = await fetch('/api/admin.php?action=comunidad_list');
        const d = await r.json();
        this.listaComunidad = Array.isArray(d) ? d : [];
      } catch (e) {
        console.error('Error al cargar comunidad:', e);
        this.listaComunidad = [];
      }
      this.cargandoComunidad = false;
    },

    abrirFormComunidad(c) {
      this.formComunidad = c
        ? { ...c, activo: c.activo === 1 || c.activo === '1' }
        : { id: null, titulo: '', descripcion: '', imagen_url: '', enlace_url: '', cta_texto: 'Participar ahora', fecha_evento: '', activo: true };
    },

    async guardarComunidad() {
      if (!this.formComunidad.titulo || !this.formComunidad.descripcion) {
        this.showToast('El título y la descripción son obligatorios');
        return;
      }

      const fd = new FormData();
      fd.append('action', 'comunidad_save');
      ['id', 'titulo', 'descripcion', 'imagen_url', 'enlace_url', 'cta_texto', 'fecha_evento']
        .forEach(field => fd.append(field, this.formComunidad[field] ?? ''));
      fd.append('activo', this.formComunidad.activo ? 1 : 0);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.showToast(this.formComunidad.id ? 'Contenido actualizado' : 'Contenido creado');
          this.formComunidad = null;
          await this.cargarComunidadAdmin();
          // Actualizar en el frontend también
          if (typeof this.cargarComunidad === 'function') {
            await this.cargarComunidad();
          }
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error de red al guardar contenido');
      }
    },

    async eliminarComunidad(id) {
      if (!confirm('¿Eliminar este contenido de comunidad permanentemente?')) return;
      const fd = new FormData();
      fd.append('action', 'comunidad_delete');
      fd.append('id', id);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.showToast('Contenido eliminado');
          await this.cargarComunidadAdmin();
          if (typeof this.cargarComunidad === 'function') {
            await this.cargarComunidad();
          }
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error de red al eliminar');
      }
    },

    async subirImagenComunidad(event) {
      const file = event.target.files[0];
      if (!file || !this.formComunidad) return;

      const fd = new FormData();
      fd.append('action', 'comunidad_imagen_upload');
      fd.append('imagen', file);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.formComunidad.imagen_url = d.url;
          this.showToast('Imagen cargada con éxito');
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error de red al subir la imagen');
      }
      event.target.value = '';
    },

    eliminarImagenComunidad() {
      if (this.formComunidad) {
        this.formComunidad.imagen_url = '';
      }
    },

    async toggleActivoComunidad(c) {
      const nuevoEstado = (c.activo === 1 || c.activo === '1') ? 0 : 1;
      const fd = new FormData();
      fd.append('action', 'comunidad_save');
      fd.append('id', c.id);
      fd.append('titulo', c.titulo);
      fd.append('descripcion', c.descripcion);
      fd.append('imagen_url', c.imagen_url ?? '');
      fd.append('enlace_url', c.enlace_url ?? '');
      fd.append('cta_texto', c.cta_texto ?? 'Participar ahora');
      fd.append('fecha_evento', c.fecha_evento ?? '');
      fd.append('activo', nuevoEstado);

      try {
        const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          c.activo = nuevoEstado;
          this.showToast('Estado actualizado');
          if (typeof this.cargarComunidad === 'function') {
            await this.cargarComunidad();
          }
        } else {
          this.showToast('Error: ' + d.error);
        }
      } catch (e) {
        this.showToast('Error al actualizar estado del contenido');
      }
    }
  };
}
