// Submódulo de Pedidos y Usuarios para el Panel de Administración

export function initOrders() {
  return {
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

    async cargarUsuarios() {
      const params = new URLSearchParams({ action: 'usuarios_list', q: this.usuarioQ });
      const r = await fetch('/api/admin.php?' + params);
      this.listaUsuarios = await r.json();
    }
  };
}
