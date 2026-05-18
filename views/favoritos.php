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
        <div class="bg-white p-4 rounded-[2rem] border border-mt-cream hover:shadow-lg transition-all flex flex-col group">
          <div class="relative">
            <img :src="p.imagen||'/mascotiendas/assets/no-image.png'" :alt="p.nombre"
                 @click="abrirProducto(p.id)"
                 class="w-full h-32 object-cover rounded-2xl mb-3 group-hover:scale-105 transition-transform cursor-pointer">
            <button @click="toggleFavorito(p.id)"
                    class="absolute top-2 right-2 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center hover:bg-red-50 transition-colors">
              <i class="fas fa-heart text-red-500 text-sm"></i>
            </button>
          </div>
          <h4 class="font-bold text-mt-brown text-sm mb-3 line-clamp-2 italic flex-grow" x-text="p.nombre"></h4>
          <div class="flex items-center justify-between mt-auto">
            <p class="font-black text-mt-brown" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
            <button @click="addToCart(p)" :disabled="!p.en_stock"
                    class="bg-mt-orange text-white p-2 rounded-xl hover:bg-orange-500 transition-colors active:scale-90 disabled:opacity-40">
              <i class="fas fa-plus text-sm"></i>
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
