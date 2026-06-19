<!-- MODAL PRODUCTO -->
<div x-show="modalProducto"
     x-cloak
     @click.self="modalProducto=null"
     @keydown.escape.window="modalProducto=null"
     class="fixed inset-0 bg-black/60 z-50 flex items-end md:items-center justify-center p-0 md:p-4 backdrop-blur-sm">

  <div x-show="modalProducto"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-8"
       x-transition:enter-end="opacity-100 translate-y-0"
       class="bg-white rounded-t-[2.5rem] md:rounded-[2.5rem] w-full md:max-w-3xl max-h-[92vh] overflow-y-auto shadow-2xl">

    <template x-if="modalProducto">
      <div>
        <!-- Header móvil -->
        <div class="sticky top-0 bg-white z-10 flex justify-between items-center px-6 pt-5 pb-3 border-b border-mt-cream">
          <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto md:hidden absolute left-1/2 -translate-x-1/2 top-2"></div>
          <h3 class="text-base font-black text-mt-brown italic line-clamp-1 pr-4" x-text="modalProducto.nombre"></h3>
          <button @click="modalProducto=null" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 transition-colors">
            <i class="fas fa-times text-slate-500 text-sm"></i>
          </button>
        </div>

        <div class="p-6 grid md:grid-cols-2 gap-6">

          <!-- Galería izquierda -->
          <div>
            <!-- Imagen principal -->
            <div class="relative bg-mt-cream rounded-2xl overflow-hidden mb-3 aspect-square">
              <img :src="imagenActiva || '/mascotiendas/assets/no-image.png'"
                   :alt="modalProducto.nombre"
                   class="w-full h-full object-contain p-4 transition-all duration-300">
              <!-- Badge sin stock -->
              <div x-show="!modalProducto.en_stock"
                   class="absolute top-3 left-3 bg-red-500 text-white text-xs font-black px-3 py-1 rounded-full uppercase">
                Sin Stock
              </div>
              <!-- Badge rebajado -->
              <div x-show="modalProducto.precio_rebajado"
                   class="absolute top-3 right-3 bg-mt-orange text-white text-xs font-black px-3 py-1 rounded-full uppercase">
                Oferta
              </div>
            </div>

            <!-- Miniaturas -->
            <div x-show="modalProducto.imagenes?.length > 1" class="flex gap-2 overflow-x-auto pb-1">
              <template x-for="img in modalProducto.imagenes" :key="img.posicion">
                <button @click="imagenActiva = img.url"
                        class="flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden border-2 transition-all"
                        :class="imagenActiva === img.url ? 'border-mt-orange' : 'border-transparent hover:border-mt-cream'">
                  <img :src="img.url" class="w-full h-full object-cover">
                </button>
              </template>
            </div>
          </div>

          <!-- Info derecha -->
          <div class="flex flex-col">

            <!-- Categorías -->
            <div class="flex flex-wrap gap-1 mb-3">
              <template x-for="cat in (modalProducto.categorias||[])" :key="cat.slug">
                <span class="text-[10px] font-black text-mt-brown uppercase tracking-widest bg-mt-cream px-2 py-0.5 rounded-full"
                      x-text="cat.nombre"></span>
              </template>
            </div>

            <!-- Nombre completo -->
            <h2 class="text-xl font-black text-mt-brown italic mb-3 leading-tight" x-text="modalProducto.nombre"></h2>

            <!-- Precio -->
            <div class="flex items-end gap-3 mb-4">
              <span class="text-3xl font-black text-mt-brown"
                    x-text="formatPrecio(modalProducto.precio_rebajado || modalProducto.precio_normal)"></span>
              <template x-if="modalProducto.precio_rebajado">
                <div class="flex flex-col">
                  <span class="text-sm line-through text-slate-400 font-bold"
                        x-text="formatPrecio(modalProducto.precio_normal)"></span>
                  <span class="text-xs font-black text-green-600"
                        x-text="'Ahorras '+formatPrecio(modalProducto.precio_normal - modalProducto.precio_rebajado)"></span>
                </div>
              </template>
            </div>

            <!-- Descripción corta -->
            <div x-show="modalProducto.descripcion_corta"
                 class="text-sm text-slate-600 leading-relaxed mb-4 prose prose-sm max-w-none"
                 x-html="modalProducto.descripcion_corta"></div>

            <!-- Cantidad + Agregar -->
            <div x-show="modalProducto.en_stock" class="flex gap-3 mb-4">
              <div class="flex items-center gap-2 bg-mt-cream rounded-xl px-3 py-2">
                <button @click="modalCantidad > 1 ? modalCantidad-- : null"
                        class="w-7 h-7 rounded-lg bg-white font-black text-mt-brown hover:bg-mt-orange hover:text-white transition-colors flex items-center justify-center shadow-sm">−</button>
                <span class="font-black text-mt-brown w-6 text-center" x-text="modalCantidad"></span>
                <button @click="modalCantidad++"
                        class="w-7 h-7 rounded-lg bg-white font-black text-mt-brown hover:bg-mt-orange hover:text-white transition-colors flex items-center justify-center shadow-sm">+</button>
              </div>
              <button @click="addToCartModal()"
                      class="flex-grow bg-mt-orange text-white py-3 rounded-xl font-black uppercase tracking-widest hover:bg-orange-500 transition-colors active:scale-95 shadow-lg">
                <i class="fas fa-shopping-basket mr-2"></i>Agregar
              </button>
            </div>

            <div x-show="!modalProducto.en_stock"
                 class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-center">
              <p class="text-red-600 font-black text-sm uppercase">Producto sin stock</p>
              <p class="text-red-400 text-xs mt-0.5">Consulta disponibilidad por WhatsApp</p>
            </div>

            <!-- WhatsApp consulta -->
            <a :href="'https://wa.me/56912345678?text=Hola!+Me+interesa+el+producto:+'+encodeURIComponent(modalProducto.nombre)"
               target="_blank"
               class="flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-[#0b3d26] text-[#0b3d26] font-black text-sm hover:bg-[#25D366] hover:text-[#0b3d26] hover:border-[#25D366] transition-colors">
              <i class="fab fa-whatsapp text-lg"></i> Consultar por WhatsApp
            </a>

          </div>
        </div>

        <!-- Descripción completa (acordeón) -->
        <div x-show="modalProducto.descripcion" class="px-6 pb-6" x-data="{abierto: false}">
          <button @click="abierto = !abierto"
                  class="w-full flex items-center justify-between p-4 bg-mt-cream rounded-2xl font-black text-mt-brown text-sm hover:bg-mt-orange hover:text-white transition-colors">
            <span>Ver descripción completa</span>
            <i class="fas transition-transform duration-200" :class="abierto ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
          </button>
          <div x-show="abierto"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0 -translate-y-2"
               x-transition:enter-end="opacity-100 translate-y-0"
               class="mt-3 p-4 bg-slate-50 rounded-2xl text-sm text-slate-600 leading-relaxed overflow-hidden"
               x-html="modalProducto.descripcion">
          </div>
        </div>

      </div>
    </template>
  </div>
</div>
