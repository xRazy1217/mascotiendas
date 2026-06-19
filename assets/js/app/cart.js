// Submódulo de Carrito de Compras para el Frontend del Cliente

export function initCart() {
  return {
    get cartCount() { 
      return this.cart.reduce((s, i) => s + i.cantidad, 0); 
    },
    
    cartTotal() { 
      return this.cart.reduce((s, i) => s + (i.precio * i.cantidad), 0); 
    },

    saveCart() { 
      localStorage.setItem('mt_cart', JSON.stringify(this.cart)); 
    },

    addToCart(p) {
      const precio = p.precio_rebajado || p.precio_normal;
      const existe = this.cart.find(i => i.id == p.id);
      if (existe) { 
        existe.cantidad++; 
      } else { 
        this.cart.push({ id: p.id, nombre: p.nombre, precio, imagen: p.imagen || null, imagen_crop: p.imagen_crop || null, cantidad: 1 }); 
      }
      this.saveCart();
      this.showToast('Agregado al carrito');
      // GA4: Enhanced Ecommerce add_to_cart
      this.trackAddToCart(p, 1);
    },

    addToCartDetalle() {
      const p      = this.varianteSeleccionada || this.productoDetalle;
      const precio = p.precio_rebajado || p.precio_normal;
      const imagen = this.imagenActiva || this.productoDetalle.imagenes?.[0]?.url || null;
      
      const activeImg = this.productoDetalle.imagenes?.find(img => img.url === imagen);
      const imagenCrop = activeImg ? activeImg.crop_config : null;
      
      const itemId = this.varianteSeleccionada ? 'v_' + this.varianteSeleccionada.id : this.productoDetalle.id;
      const nombre = this.varianteSeleccionada
        ? this.productoDetalle.nombre + ' (' + (this.varianteSeleccionada.label || '') + ')'
        : this.productoDetalle.nombre;
      const existe = this.cart.find(i => i.id == itemId);
      if (existe) { 
        existe.cantidad += this.modalCantidad; 
      } else { 
        this.cart.push({ id: itemId, producto_id: this.productoDetalle.id, nombre, precio, imagen, imagen_crop: imagenCrop || null, cantidad: this.modalCantidad }); 
      }
      this.saveCart();
      this.guardarCarritoAbandonado();
      this.showToast(this.modalCantidad + ' producto(s) agregado(s) al carrito');
      // GA4: Enhanced Ecommerce add_to_cart
      this.trackAddToCart(this.productoDetalle, this.modalCantidad, this.varianteSeleccionada);
    },

    async guardarCarritoAbandonado() {
      if (!this.usuario || this.cart.length === 0) return;
      const fd = new FormData();
      fd.append('action', 'carrito_save');
      fd.append('email', this.usuario.email || '');
      fd.append('items', JSON.stringify(this.cart));
      fd.append('total', this.cartTotal());
      fetch('/api/tracking.php', { method: 'POST', body: fd });
    },

    async recuperarCarrito() {
      if (this.cart.length > 0) return; // ya tiene items
      const r = await fetch('/api/tracking.php?action=carrito_get');
      const d = await r.json();
      if (d.items && d.items.length > 0) {
        this.cart = d.items;
        this.saveCart();
        this.showToast('Recuperamos tu carrito anterior');
      }
    }
  };
}
