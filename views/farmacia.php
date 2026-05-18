<div x-show="page==='farmacia'" x-cloak class="fade-in">
  <div class="bg-mt-brown p-10 rounded-[3rem] mb-8 text-white border-b-8 border-mt-orange shadow-xl flex justify-between items-center">
    <h2 class="text-4xl font-black uppercase italic tracking-tighter">Farmacia <span class="text-mt-orange">Veterinaria</span></h2>
    <i class="fas fa-briefcase-medical text-5xl opacity-20"></i>
  </div>

  <div x-show="cargando" class="text-center py-20 text-mt-orange">
    <i class="fas fa-spinner fa-spin text-4xl"></i>
  </div>

  <div x-show="!cargando" class="grid grid-cols-2 md:grid-cols-3 gap-5">
    <template x-for="p in productos" :key="p.id">
      <div @click="abrirProducto(p.id)"
           class="bg-white p-5 rounded-[2rem] border border-mt-cream hover:shadow-lg transition-all cursor-pointer group flex flex-col">
        <div class="relative mb-4">
          <img :src="p.imagen||'/mascotiendas/assets/no-image.png'" :alt="p.nombre"
               class="w-full h-48 object-cover rounded-2xl group-hover:scale-105 transition-transform">
          <span x-show="!p.en_stock"
                class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Sin stock</span>
        </div>
        <h4 class="font-bold text-mt-brown text-sm mb-4 line-clamp-2 italic flex-grow" x-text="p.nombre"></h4>
        <div class="flex items-center justify-between mt-auto">
          <div>
            <template x-if="p.precio_rebajado">
              <span class="text-xs line-through text-slate-400 block" x-text="formatPrecio(p.precio_normal)"></span>
            </template>
            <p class="font-black text-lg text-mt-brown" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
          </div>
          <button @click.stop="addToCart(p)" :disabled="!p.en_stock"
                  class="bg-mt-orange text-white p-2.5 rounded-xl hover:bg-orange-500 transition-colors active:scale-90 disabled:opacity-40">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>
    </template>
  </div>
</div>
