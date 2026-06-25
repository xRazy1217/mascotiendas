<div x-show="page==='farmacia'" x-cloak class="fade-in">
  <div class="bg-mt-orange p-10 rounded-[3rem] mb-8 text-white border-b-8 border-mt-brown shadow-xl flex justify-between items-center">
    <h1 class="text-4xl font-black uppercase italic tracking-tighter">Farmacia <span class="text-mt-brown">Veterinaria</span></h1>
    <i class="fas fa-briefcase-medical text-5xl opacity-20"></i>
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
                 width="400" height="300" loading="lazy" decoding="async"
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
</div>
