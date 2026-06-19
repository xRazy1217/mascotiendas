// Submódulo de Analytics y DataLayer para E-commerce (GA4 / GTM)

export function initAnalytics() {
  return {
    // ─── DATALAYER PUSH ────────────────────────────────
    gtmPush(event, data = {}) {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event, ...data });

      // Fallback para gtag.js directo si no se usa GTM
      if (typeof window.gtag === 'function') {
        if (event === 'conversion') {
          window.gtag('event', 'conversion', {
            send_to: data.send_to,
            value: data.value,
            currency: data.currency
          });
        } else if (event === 'search') {
          window.gtag('event', 'search', { search_term: data.search_term });
        } else if (data.ecommerce) {
          window.gtag('event', event, data.ecommerce);
        }
      }
    },

    // ─── E-COMMERCE EVENTS (GA4 Enhanced Ecommerce) ───
    trackViewItem(product) {
      if (!product) return;
      this.gtmPush('view_item', {
        ecommerce: {
          currency: 'CLP',
          value: Number(product.precio_rebajado || product.precio_normal),
          items: [this._mapProductItem(product, 0)]
        }
      });
    },

    trackViewItemList(products, listName = 'Catálogo') {
      if (!products || !products.length) return;
      this.gtmPush('view_item_list', {
        ecommerce: {
          item_list_name: listName,
          items: products.slice(0, 20).map((p, i) => this._mapProductItem(p, i))
        }
      });
    },

    trackAddToCart(product, quantity = 1, variant = null) {
      if (!product) return;
      const item = this._mapProductItem(product, 0);
      item.quantity = quantity;
      if (variant) item.item_variant = variant.label || '';
      this.gtmPush('add_to_cart', {
        ecommerce: {
          currency: 'CLP',
          value: Number(product.precio_rebajado || product.precio_normal) * quantity,
          items: [item]
        }
      });
    },

    trackRemoveFromCart(product, quantity = 1) {
      if (!product) return;
      const item = this._mapProductItem(product, 0);
      item.quantity = quantity;
      this.gtmPush('remove_from_cart', {
        ecommerce: {
          currency: 'CLP',
          value: Number(product.precio_rebajado || product.precio_normal) * quantity,
          items: [item]
        }
      });
    },

    trackBeginCheckout(cartItems) {
      if (!cartItems || !cartItems.length) return;
      let total = 0;
      const items = cartItems.map((item, i) => {
        const price = Number(item.precio_rebajado || item.precio_normal || item.precio);
        const qty = Number(item.cantidad || 1);
        total += price * qty;
        return {
          item_id: String(item.id || item.producto_id),
          item_name: item.nombre,
          price: price,
          quantity: qty,
          index: i
        };
      });
      this.gtmPush('begin_checkout', {
        ecommerce: { currency: 'CLP', value: total, items }
      });
    },

    trackPurchase(orderId, total, items = []) {
      this.gtmPush('purchase', {
        ecommerce: {
          transaction_id: String(orderId),
          currency: 'CLP',
          value: Number(total),
          shipping: 0,
          items: items.map((item, i) => ({
            item_id: String(item.id || item.producto_id),
            item_name: item.nombre,
            price: Number(item.precio),
            quantity: Number(item.cantidad || 1),
            index: i
          }))
        }
      });

      // Google Ads Conversion tracking automático si configurado
      const config = window.MT_SEO_CONFIG || {};
      if (config.gads_conversion_id && config.gads_conversion_label) {
        this.trackConversion(config.gads_conversion_id, config.gads_conversion_label, total);
      }
    },

    trackSearch(searchTerm) {
      if (!searchTerm) return;
      this.gtmPush('search', { search_term: searchTerm });
    },

    trackSelectItem(product, listName = 'Catálogo') {
      if (!product) return;
      this.gtmPush('select_item', {
        ecommerce: {
          item_list_name: listName,
          items: [this._mapProductItem(product, 0)]
        }
      });
    },

    // ─── CONVERSION EVENTS (Google Ads) ───────────────
    trackConversion(conversionId, conversionLabel, value = 0) {
      if (!conversionId) return;
      this.gtmPush('conversion', {
        send_to: conversionId + '/' + conversionLabel,
        value: value,
        currency: 'CLP'
      });
    },

    // ─── HELPER: Map product to GA4 item ──────────────
    _mapProductItem(product, index) {
      return {
        item_id: String(product.id),
        item_name: product.nombre || '',
        item_brand: product.marca_nombre || 'Mascotiendas',
        item_category: product.categorias
          ? (typeof product.categorias === 'string' ? product.categorias.split(',')[0].trim() : (product.categorias[0]?.nombre || ''))
          : '',
        price: Number(product.precio_rebajado || product.precio_normal),
        index: index,
        quantity: 1
      };
    }
  };
}
