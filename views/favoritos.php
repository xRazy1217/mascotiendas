<div x-show="page==='favoritos'" x-cloak class="fade-in max-w-4xl mx-auto">
  <div class="flex items-center gap-4 mb-8">
    <h2 class="text-2xl font-black text-mt-brown uppercase tracking-tighter">Mis Favoritos</h2>
    <div class="h-px flex-grow bg-mt-cream"></div>
  </div>

  <div x-show="!usuario" class="text-center py-16 bg-white rounded-[2rem] border border-mt-cream">
    <i class="fas fa-heart text-5xl text-mt-cream mb-4"></i>
    <p class="font-bold text-slate-400 italic mb-4">Inicia sesion para ver tus favoritos</p>
    <button @click="page='login'" class="bg-mt-orange text-white px-8 py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
      Iniciar Sesion
    </button>
  </div>

  <div x-show="usuario">
    <div x-show="favoritos.length===0" class="text-center py-16 bg-white rounded-[2rem] border border-mt-cream">
      <i class="fas fa-heart text-5xl text-mt-cream mb-4"></i>
      <p class="font-bold text-slate-400 italic mb-4">No tienes productos favoritos aun</p>
      <button @click="page='tienda';cargarProductos()" class="bg-mt-orange text-white px-8 py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
        Ver Tienda
      </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <template x-for="p in favoritos" :key="p.id">
        <div class="premium-card bg-white p-4 rounded-[2.5rem] border border-mt-cream flex flex-col justify-between shadow-sm relative overflow-hidden group">
          <div class="relative mb-3">
            <div @click="abrirProducto(p.id, true, p.slug)"
                 class="overflow-hidden rounded-[2rem] aspect-[4/3] w-full bg-mt-cream cursor-pointer relative">
              <img :src="getProductImage(p.imagen)" :alt="p.nombre"
                   :style="getImageStyle(p.imagen_crop, 'catalogo')"
                   width="400" height="300"
                   class="premium-card-img w-full h-full object-cover">
            </div>
            <button @click="toggleFavorito(p.id)"
                    :aria-label="'Quitar ' + p.nombre + ' de favoritos'"
                    class="absolute top-3 right-3 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center hover:bg-red-50 transition-colors z-10">
              <i class="fas fa-heart text-red-500 text-sm"></i>
            </button>
            <span x-show="!p.en_stock"
                  class="absolute top-3 left-3 bg-red-500 text-white text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider z-10 shadow-sm">Sin stock</span>
          </div>
          <h4 class="font-black text-mt-brown text-sm leading-tight mb-3 line-clamp-2 italic flex-grow hover:text-mt-orange transition-colors duration-200" x-text="p.nombre"></h4>
          <div class="flex items-center justify-between mt-auto">
            <p class="font-black text-mt-brown" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
            <button @click="addToCart(p)" :disabled="!p.en_stock"
                    :aria-label="'Agregar ' + p.nombre + ' al carrito'"
                    class="premium-btn bg-mt-orange text-white p-2.5 rounded-xl hover:bg-orange-500 shadow-md active:scale-95 disabled:opacity-40">
              <i class="fas fa-plus text-sm"></i>
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
