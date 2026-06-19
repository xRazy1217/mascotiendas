// Submódulo de Dashboard, Estadísticas, Notificaciones y Configuración General

export function initDashboard() {
  return {
    // ─── DASHBOARD & STATS ───────────────────────────────
    async cargarStats() {
      const r = await fetch('/api/admin.php?action=stats');
      this.stats = await r.json();
    },

    async cargarBajoStock() {
      const r = await fetch('/api/admin.php?action=bajo_stock');
      this.bajoStockList = await r.json();
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

    async invertirColores() {
      const color1 = this.config.theme_color_primary || '#7F5234';
      const color2 = this.config.theme_color_secondary || '#F7941D';
      
      this.config.theme_color_primary = color2;
      this.config.theme_color_secondary = color1;
      
      await this.guardarConfig('theme_color_primary', color2);
      await this.guardarConfig('theme_color_secondary', color1);
      
      this.showToast('Colores principales invertidos. Recarga para aplicar.');
    },

    async restablecerColores() {
      if (!confirm('¿Restablecer los colores del diseño a sus valores predeterminados (Marrón, Naranjo y Crema)?')) return;
      this.config.theme_color_primary = '#7F5234';
      this.config.theme_color_secondary = '#F7941D';
      this.config.theme_color_cream = '#F9F1E7';
      await this.guardarConfig('theme_color_primary', '#7F5234');
      await this.guardarConfig('theme_color_secondary', '#F7941D');
      await this.guardarConfig('theme_color_cream', '#F9F1E7');
      this.showToast('Colores restablecidos. Recarga para aplicar.');
    },

    // ─── TEXTOS ─────────────────────────────────────────
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
    }
  };
}
