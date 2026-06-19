// Submódulo de Proceso de Pago (Checkout) y Suscripciones para el Cliente

export function initCheckout() {
  return {
    totalConDescuento() {
      const zona     = this.zonas.find(z => z.id == this.checkout.zona_id);
      const delivery = (this.checkout.metodo_entrega === 'delivery' && zona) ? (parseInt(zona.costo) || 0) : 0;
      const primerPedido = this.checkout.descuento_primer_pedido || 0;
      return Math.max(0, this.cartTotal() + delivery - (this.checkout.descuento || 0) - primerPedido);
    },

    async aplicarCupon(codigo, total, onOk, onError) {
      if (!codigo) return;
      const fd = new FormData();
      fd.append('action', 'cupon_validar');
      fd.append('codigo', codigo);
      fd.append('total', total);
      const r = await fetch('/api/admin.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.error) onError(d.error);
      else { this.checkout.descuento = d.descuento; onOk(d); }
    },

    async realizarPedido() {
      // Validación básica
      if (!this.checkout.nombre || !this.checkout.email) {
        this.checkoutError = 'Nombre y email son requeridos'; return;
      }
      if (this.checkout.metodo_entrega === 'delivery' && (!this.checkout.direccion || !this.checkout.ciudad)) {
        this.checkoutError = 'Dirección y ciudad son requeridas para delivery'; return;
      }
      if (!this.checkout.metodo_pago) {
        this.checkoutError = 'Selecciona un método de pago'; return;
      }

      const totalDescuento = this.checkout.descuento || 0;
      const descuentoPrimerPedido = this.checkout.descuento_primer_pedido || 0;
      const totalFinal = this.totalConDescuento();

      // Guardar pedido en base de datos
      let dbOrderId = 'WA-' + Date.now();
      try {
        const orderData = {
          nombre: this.checkout.nombre,
          email: this.checkout.email,
          telefono: this.checkout.telefono,
          direccion: this.checkout.metodo_entrega === 'delivery' ? this.checkout.direccion : '',
          ciudad: this.checkout.metodo_entrega === 'delivery' ? this.checkout.ciudad : '',
          sucursal_retiro: this.checkout.metodo_entrega === 'retiro' ? this.checkout.sucursal_retiro : '',
          notas: this.checkout.notas,
          metodo_entrega: this.checkout.metodo_entrega,
          metodo_pago: this.checkout.metodo_pago,
          zona_id: this.checkout.metodo_entrega === 'delivery' ? this.checkout.zona_id : null,
          descuento: totalDescuento + descuentoPrimerPedido,
          items: this.cart.map(i => {
            const itemIdStr = String(i.id);
            const isVariant = itemIdStr.startsWith('v_');
            const numericId = isVariant ? parseInt(itemIdStr.replace('v_', '')) : i.id;
            return {
              id: i.producto_id || numericId,
              cantidad: i.cantidad
            };
          })
        };

        const response = await fetch('/api/pedidos.php?action=crear', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(orderData)
        });

        const result = await response.json();
        if (result.ok) {
          dbOrderId = result.pedido_id;
        } else {
          console.error('Error al registrar pedido en BD:', result.error);
        }
      } catch (e) {
        console.error('Error de red al registrar pedido:', e);
      }

      // Aviso horario despachos
      const hora = new Date().getHours();
      const despachoMsg = hora >= 20
        ? 'Pedido recibido despues de las 20:00 hrs. Sera despachado manana por la manana.'
        : 'Tu pedido sera despachado hoy.';

      // Armar listado de productos
      const productos = this.cart.map(i => `- ${i.nombre} x${i.cantidad} -> ${this.formatPrecio(i.precio * i.cantidad)}`).join('\n');

      // Entrega / Retiro
      let entregaMsg = '';
      if (this.checkout.metodo_entrega === 'retiro') {
        const sucursales = { Balmaceda: 'Av. Balmaceda 4521 Local #2, La Serena', Geronimo: 'Geronimo Mendez, Coquimbo', Alessandri: 'Alessandri 147, El Llano' };
        entregaMsg = `Retiro en tienda: ${sucursales[this.checkout.sucursal_retiro] || this.checkout.sucursal_retiro}`;
      } else {
        const zona = this.zonas.find(z => z.id == this.checkout.zona_id);
        entregaMsg = `Delivery a: ${this.checkout.direccion}, ${this.checkout.ciudad}`;
        if (zona) entregaMsg += ` (${zona.nombre})`;
      }

      // Descuentos aplicados
      let descuentosMsg = '';
      if (totalDescuento > 0) descuentosMsg += `\nCupon: -${this.formatPrecio(totalDescuento)}`;
      if (descuentoPrimerPedido > 0) {
        const pctText = this.config.descuento_primer_pedido_valor || 10;
        descuentosMsg += `\nDescuento primer pedido (${pctText}%): -${this.formatPrecio(descuentoPrimerPedido)}`;
      }

      const msg = encodeURIComponent(
        `*Nuevo Pedido - Mascotiendas (N° ${dbOrderId})*\n\n`
        + `*Cliente:* ${this.checkout.nombre}\n`
        + `*Email:* ${this.checkout.email}\n`
        + `*Telefono:* ${this.checkout.telefono || 'No indicado'}\n\n`
        + `*Productos:*\n${productos}\n\n`
        + `${entregaMsg}\n`
        + `*Pago:* ${this.checkout.metodo_pago}${descuentosMsg}\n`
        + (this.checkout.horario ? `*Horario preferido:* ${this.checkout.horario}\n` : '')
        + `*Total: ${this.formatPrecio(totalFinal)}*\n\n`
        + (this.checkout.notas ? `*Notas:* ${this.checkout.notas}\n\n` : '')
        + despachoMsg
      );

      const botNumber = (this.config.bot_whatsapp_number || '56953793135').replace(/\D/g, '');
      window.open(`https://wa.me/${botNumber}?text=${msg}`, '_blank');

      // GA4: Enhanced Ecommerce purchase
      this.trackPurchase(dbOrderId, totalFinal, this.cart);

      // Limpiar carrito tras realizar pedido
      this.cart = []; this.saveCart();
      this.checkout.descuento = 0;
      this.checkout.descuento_primer_pedido = 0;
      this.page = 'home';
      this.showToast('✅ Redirigiendo a WhatsApp para confirmar tu pedido');
    },

    async suscribir() {
      const fd = new FormData();
      fd.append('action', 'subscribe');
      fd.append('email', this.newsletterEmail);
      try {
        const r = await fetch('/api/newsletter.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) {
            this.newsletterEmail = '';
            this.showToast(d.message);
        } else {
            this.showToast(d.error);
        }
      } catch (e) {
          this.showToast('Error de conexión');
      }
    },

    async cargarZonas() {
      if (!this.zonas.length) {
        const r = await fetch('/api/contenido.php?action=zonas');
        this.zonas = await r.json();
      }

      // Calcular descuento primer pedido si es aplicable
      if (this.config.descuento_primer_pedido === '1' && this.usuario) {
        try {
          const r = await fetch('/api/pedidos.php?action=mis');
          const pedidos = await r.json();
          if (!pedidos.error && pedidos.length === 0) {
            const pct = parseFloat(this.config.descuento_primer_pedido_valor || 10) / 100;
            this.checkout.descuento_primer_pedido = Math.round(this.cartTotal() * pct);
          } else {
            this.checkout.descuento_primer_pedido = 0;
          }
        } catch (e) {
          console.error('Error al verificar primer pedido:', e);
          this.checkout.descuento_primer_pedido = 0;
        }
      } else {
        this.checkout.descuento_primer_pedido = 0;
      }

      // GA4: begin_checkout cuando se cargan las zonas (entrada a checkout)
      if (this.cart.length > 0) this.trackBeginCheckout(this.cart);
    }
  };
}
