<div x-show="page==='tienda'" x-cloak class="fade-in">
  <h1 class="text-3xl font-black text-mt-brown mb-6 uppercase tracking-tighter text-center italic">Catálogo</h1>

  <div class="flex flex-wrap gap-3 mb-6">
    <input x-model="filtroQ" @input.debounce.400ms="paginaActual=1;cargarProductos()"
           type="text" placeholder="Buscar producto..."
           class="flex-grow min-w-[200px] px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange text-sm font-bold">
    <select x-model="filtroCategoria" @change="paginaActual=1;cargarProductos()"
            class="px-4 py-2 rounded-xl border border-mt-cream focus:outline-none text-sm font-bold bg-white">
      <option value="">Todas las categorías</option>
      <template x-for="c in categorias" :key="c.id">
        <option :value="c.slug" x-text="c.nombre+' ('+c.total+')'"></option>
      </template>
    </select>
  </div>

  <div x-show="cargando" class="text-center py-20 text-mt-orange">
    <i class="fas fa-spinner fa-spin text-4xl"></i>
  </div>

  <div x-show="!cargando" class="grid grid-cols-2 md:grid-cols-3 gap-5">
    <template x-for="p in productos" :key="p.id">
      <div @click="abrirProducto(p.id, true, p.slug)"
           class="premium-card bg-white p-5 rounded-[2.5rem] border border-mt-cream cursor-pointer group flex flex-col justify-between shadow-sm relative overflow-hidden">
        <div class="relative mb-4">
          <div class="overflow-hidden rounded-[2rem] aspect-[4/3] w-full bg-mt-cream relative">
            <img :src="getProductImage(p.imagen)" :alt="p.nombre"
                 :style="getImageStyle(p.imagen_crop, 'catalogo')"
                 width="400" height="300"
                 class="premium-card-img w-full h-full object-cover">
          </div>
          <span x-show="!p.en_stock"
                class="absolute top-3 left-3 bg-red-500 text-white text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider z-10 shadow-sm">Sin stock</span>
          <span x-show="p.precio_rebajado"
                class="absolute top-3 right-3 glass-effect text-mt-orange text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider z-10 shadow-sm">Oferta</span>
        </div>
        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5"
              x-text="p.categorias?.split(',')[0]||''"></span>
        <h4 class="font-black text-mt-brown text-sm leading-tight mb-3 line-clamp-2 italic flex-grow hover:text-mt-orange transition-colors duration-200" x-text="p.nombre"></h4>
        <div class="flex items-center justify-between mt-auto">
          <div>
            <template x-if="p.precio_rebajado">
              <span class="text-xs line-through text-slate-400 block font-bold" x-text="formatPrecio(p.precio_normal)"></span>
            </template>
            <p class="font-black text-lg text-mt-brown" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
          </div>
          <button @click.stop="addToCart(p)" :disabled="!p.en_stock"
                  :aria-label="'Agregar ' + p.nombre + ' al carrito'"
                  class="premium-btn bg-mt-orange text-white p-3 rounded-xl hover:bg-orange-500 shadow-md active:scale-95 disabled:opacity-40">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>
    </template>
  </div>

  <div x-show="!cargando && productos.length===0" class="text-center py-20 text-slate-400 italic">
    <i class="fas fa-search text-4xl mb-3 opacity-30"></i>
    <p class="font-bold">No se encontraron productos</p>
  </div>

  <div x-show="totalPaginas>1" class="flex justify-center gap-2 mt-8">
    <button @click="pagina--;cargarProductos()" :disabled="pagina<=1"
            aria-label="Página anterior"
            class="px-4 py-2 rounded-xl bg-mt-cream font-black text-mt-brown disabled:opacity-40 hover:bg-mt-brown hover:text-white transition-colors">
      <i class="fas fa-chevron-left"></i>
    </button>
    <span class="px-4 py-2 font-bold text-mt-brown" x-text="pagina+' / '+totalPaginas"></span>
    <button @click="pagina++;cargarProductos()" :disabled="pagina>=totalPaginas"
            aria-label="Página siguiente"
            class="px-4 py-2 rounded-xl bg-mt-cream font-black text-mt-brown disabled:opacity-40 hover:bg-mt-brown hover:text-white transition-colors">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>
</div>
