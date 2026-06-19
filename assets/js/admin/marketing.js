// Submódulo de Marketing, Cupones, Blog, Reseñas y Campañas para el Panel de Admin

export function initMarketing() {
  return {
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

    // ─── REVIEWS (RESEÑAS) ─────────────────────────────────
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

    // ─── NEWSLETTER Y RECORDATORIOS ───────────────────────────
    async cargarNewsletter() {
      const r = await fetch('/api/admin.php?action=newsletter_list');
      this.listaNewsletter = await r.json();
    },

    async cargarBlacklist() {
      const r = await fetch('/api/admin.php?action=blacklist_list');
      this.listaBlacklist = await r.json();
    },

    async eliminarBlacklist(id) {
      if (!confirm('¿Deseas remover este correo de la lista de exclusión? Volverá a recibir comunicaciones de marketing.')) return;
      const fd = new FormData();
      fd.append('action', 'blacklist_delete');
      fd.append('id', id);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast('Correo removido de la lista de exclusión');
        this.cargarBlacklist();
      } else {
        this.showToast('Error: ' + d.error);
      }
    },

    async cargarCarritos() {
      const r = await fetch('/api/admin.php?action=carritos_abandonados');
      this.listaCarritos = await r.json();
    },

    async toggleSuscriptor(id) {
      const fd = new FormData();
      fd.append('action', 'newsletter_suscriptor_toggle');
      fd.append('id', id);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast('Estado de suscriptor actualizado');
        this.cargarNewsletter();
      }
    },

    async eliminarSuscriptor(id) {
      if (!confirm('¿Eliminar este suscriptor permanentemente?')) return;
      const fd = new FormData();
      fd.append('action', 'newsletter_suscriptor_delete');
      fd.append('id', id);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast('Suscriptor eliminado');
        this.cargarNewsletter();
      }
    },

    async enviarCampanaMasiva() {
      if (!this.campanaAsunto || !this.campanaMensaje) {
        this.showToast('Asunto y mensaje requeridos');
        return;
      }
      if (!confirm('¿Estás seguro de que deseas enviar esta campaña a todos los suscriptores activos? Se realizarán envíos de correo reales.')) return;
      
      const fd = new FormData();
      fd.append('action', 'newsletter_enviar_campana');
      fd.append('asunto', this.campanaAsunto);
      fd.append('mensaje', this.campanaMensaje);
      
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast(d.mensaje || 'Campaña enviada exitosamente');
        this.campanaAsunto = '';
        this.campanaMensaje = '';
        this.seccionMarketingTab = 'resumen';
        this.cargarNewsletter();
      } else {
        this.showToast('Error: ' + d.error);
      }
    },

    async enviarRecordatorioManual(id, tipo) {
      const fd = new FormData();
      fd.append('action', 'carrito_enviar_recordatorio');
      fd.append('id', id);
      fd.append('tipo', tipo);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) {
        this.showToast(d.mensaje || 'Recordatorio enviado');
        this.cargarCarritos();
      } else {
        this.showToast('Error: ' + d.error);
      }
    },

    exportarNewsletter() {
      const csv = 'Email,Fecha\n' + this.listaNewsletter.map(s => `${s.email},${s.fecha_registro || s.creado_en}`).join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'newsletter_' + new Date().toISOString().slice(0,10) + '.csv';
      a.click();
    },

    // ─── CAMPANAS EMERGENTES ─────────────────────────────────
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
