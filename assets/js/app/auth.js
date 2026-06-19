// Submódulo de Autenticación y Perfil de Usuario para el Cliente

export function initAuth() {
  return {
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

    // Direcciones de Despacho
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
    }
  };
}
