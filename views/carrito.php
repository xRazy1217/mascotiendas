<div x-show="page==='carrito'" x-cloak class="fade-in max-w-2xl mx-auto">
  <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-mt-cream">
    <h2 class="text-2xl font-black text-mt-brown mb-6 uppercase text-center">Mi Carrito</h2>

    <div x-show="cart.length===0" class="py-16 text-center text-slate-400">
      <i class="fas fa-shopping-basket text-5xl mb-4 opacity-30"></i>
      <p class="font-bold italic">Tu carrito está vacío</p>
      <button @click="page='tienda';cargarProductos()" class="mt-4 text-mt-brown hover:text-mt-orange font-black text-sm hover:underline">
        Ver productos →
      </button>
    </div>

    <div class="space-y-3 mb-6">
      <template x-for="(item,i) in cart" :key="i">
        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-mt-cream">
          <div class="w-14 h-14 overflow-hidden rounded-xl flex-shrink-0 bg-mt-cream">
            <img :src="getProductImage(item.imagen)"
                 :style="getImageStyle(item.imagen_crop, 'miniatura')"
                 class="w-full h-full object-cover">
          </div>
          <div class="flex-grow min-w-0">
            <p class="font-bold text-mt-brown text-sm line-clamp-1 italic" x-text="item.nombre"></p>
            <p class="text-mt-brown font-black text-sm" x-text="formatPrecio(item.precio)"></p>
          </div>
          <div class="flex items-center gap-1 flex-shrink-0">
            <button @click="item.cantidad>1?item.cantidad--:cart.splice(i,1);saveCart()"
                    class="w-7 h-7 rounded-lg bg-mt-cream font-black text-mt-brown hover:bg-mt-brown hover:text-white transition-colors flex items-center justify-center text-sm">−</button>
            <span class="font-black text-mt-brown w-6 text-center text-sm" x-text="item.cantidad"></span>
            <button @click="item.cantidad++;saveCart()"
                    class="w-7 h-7 rounded-lg bg-mt-cream font-black text-mt-brown hover:bg-mt-brown hover:text-white transition-colors flex items-center justify-center text-sm">+</button>
          </div>
          <p class="font-black text-mt-brown text-sm flex-shrink-0" x-text="formatPrecio(item.precio*item.cantidad)"></p>
          <button @click="cart.splice(i,1);saveCart()" class="text-red-400 hover:text-red-600 flex-shrink-0">
            <i class="fas fa-trash text-sm"></i>
          </button>
        </div>
      </template>
    </div>

    <div x-show="cart.length>0">
      <div class="flex justify-between items-center text-xl font-black p-5 bg-mt-cream rounded-2xl mb-4">
        <span>Total:</span>
        <span class="text-mt-brown" x-text="formatPrecio(cartTotal())"></span>
      </div>
      <button @click="page='checkout'"
              class="w-full bg-mt-brown text-white py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-mt-orange transition-colors">
        Finalizar Compra
      </button>
    </div>
  </div>
</div>
