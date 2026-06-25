<div x-show="page==='producto'" x-cloak class="fade-in">

  <div x-show="cargandoProducto" class="flex items-center justify-center py-32">
    <i class="fas fa-spinner fa-spin text-mt-orange text-4xl"></i>
  </div>

  <template x-if="productoDetalle && !productoDetalle.error && !cargandoProducto">
    <div>
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-6">
        <button @click="page='home'" class="hover:text-mt-orange transition-colors">Inicio</button>
        <span>/</span>
        <button @click="page='tienda';cargarProductos()" class="hover:text-mt-orange transition-colors">Tienda</button>
        <span>/</span>
        <span class="text-mt-brown line-clamp-1" x-text="productoDetalle.nombre"></span>
      </div>

      <div class="grid md:grid-cols-2 gap-8 mb-10">

        <!-- Galería -->
        <div>
          <div class="bg-mt-cream rounded-[2rem] overflow-hidden mb-3 aspect-square flex items-center justify-center relative">
            <img :src="getProductImage(imagenActiva)"
                 :alt="productoDetalle.nombre"
                 :style="getImageStyle(getActiveImageCrop(), 'detalle')"
                 width="600" height="600"
                 class="w-full h-full transition-all duration-300">
            <!-- Badge oferta -->
            <div x-show="productoDetalle.precio_rebajado"
                 class="absolute top-3 left-3 bg-mt-orange text-white text-xs font-black px-3 py-1 rounded-full uppercase">
              Oferta
            </div>
          </div>
          <div x-show="productoDetalle.imagenes?.length > 1" class="flex gap-2 overflow-x-auto pb-1">
            <template x-for="img in productoDetalle.imagenes" :key="img.posicion">
              <button @click="imagenActiva=img.url"
                      aria-label="Ver miniatura del producto"
                      class="flex-shrink-0 w-20 h-20 rounded-2xl overflow-hidden border-2 transition-all bg-mt-cream"
                      :class="imagenActiva===img.url?'border-mt-orange':'border-mt-cream hover:border-mt-orange'">
                <img :src="getProductImage(img.url)"
                     :style="getImageStyle(img.crop_config, 'miniatura')"
                     width="80" height="80" loading="lazy" decoding="async"
                     class="w-full h-full object-cover">
              </button>
            </template>
          </div>
        </div>

        <!-- Info -->
        <div class="flex flex-col">

          <!-- Categorías -->
          <div class="flex flex-wrap gap-1 mb-3">
            <template x-for="cat in (productoDetalle.categorias||[])" :key="cat.slug">
              <span class="text-[10px] font-black text-mt-brown uppercase tracking-widest bg-mt-cream px-3 py-1 rounded-full"
                    x-text="cat.nombre"></span>
            </template>
          </div>

          <h1 class="text-2xl md:text-3xl font-black text-mt-brown italic mb-2 leading-tight"
              x-text="productoDetalle.nombre"></h1>

          <!-- Rating resumen -->
          <div x-show="reviewStats.total > 0" class="flex items-center gap-2 mb-4">
            <div class="flex gap-0.5">
              <template x-for="i in 5" :key="i">
                <i :class="i <= Math.round(reviewStats.promedio) ? 'fas fa-star text-yellow-400' : 'far fa-star text-slate-300'"
                   class="text-sm"></i>
              </template>
            </div>
            <span class="text-xs font-bold text-slate-500"
                  x-text="Number(reviewStats.promedio).toFixed(1)+' ('+reviewStats.total+' reseñas)'"></span>
          </div>

          <!-- FOMO -->
          <div class="space-y-2 mb-4">
            <div x-show="fomoViendo > 1"
                 class="flex items-center gap-2 text-xs font-bold text-slate-500 bg-slate-50 px-3 py-2 rounded-xl">
              <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
              <span x-text="fomoViendo+' personas están viendo este producto ahora'"></span>
            </div>
            <template x-for="evento in fomoCompras" :key="evento.creado_en">
              <div class="flex items-center gap-2 text-xs font-bold text-slate-500 bg-slate-50 px-3 py-2 rounded-xl">
                <i class="fas fa-shopping-bag text-mt-orange"></i>
                <span x-text="(evento.nombre||'Alguien')+' compró este producto hace '+tiempoRelativo(evento.creado_en)"></span>
              </div>
            </template>
          </div>

          <!-- Precio -->
          <div class="flex items-end gap-4 mb-4">
            <span class="text-4xl font-black text-mt-brown"
                  x-text="formatPrecio((varianteSeleccionada?.precio_rebajado||varianteSeleccionada?.precio_normal)||(productoDetalle.precio_rebajado||productoDetalle.precio_normal))"></span>
            <template x-if="varianteSeleccionada?.precio_rebajado||productoDetalle.precio_rebajado">
              <div class="flex flex-col pb-1">
                <span class="text-sm line-through text-slate-400 font-bold"
                      x-text="formatPrecio(varianteSeleccionada?.precio_normal||productoDetalle.precio_normal)"></span>
                <span class="text-xs font-black text-green-600">
                  Ahorras <span x-text="formatPrecio((varianteSeleccionada?.precio_normal||productoDetalle.precio_normal)-(varianteSeleccionada?.precio_rebajado||productoDetalle.precio_rebajado))"></span>
                </span>
              </div>
            </template>
          </div>

          <!-- Variantes -->
          <div x-show="productoDetalle.variantes?.length > 0" class="mb-4">
            <template x-for="grupo in productoDetalle.grupos_atributos||[]" :key="grupo.nombre">
              <div class="mb-3">
                <p class="text-xs font-black text-mt-brown uppercase tracking-widest mb-2" x-text="grupo.nombre"></p>
                <div class="flex flex-wrap gap-2">
                  <template x-for="opcion in grupo.opciones" :key="opcion.valor_id">
                    <button @click="seleccionarVariante(opcion.valor_id)"
                            :class="varianteSeleccionada?.id==opcion.valor_id?'bg-mt-orange text-white border-mt-orange':'bg-white text-mt-brown border-mt-cream hover:border-mt-orange'"
                            :disabled="!opcion.disponible"
                            class="px-4 py-2 rounded-xl border-2 font-bold text-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                            x-text="opcion.valor"></button>
                  </template>
                </div>
              </div>
            </template>
          </div>

          <!-- Descripcion corta -->
          <div x-show="productoDetalle.descripcion_corta"
               class="text-sm text-slate-600 leading-relaxed mb-4 bg-slate-50 p-4 rounded-2xl desc-content"
               x-html="productoDetalle.descripcion_corta"></div>

          <!-- Stock -->
          <div x-show="varianteSeleccionada ? varianteSeleccionada.en_stock : productoDetalle.en_stock"
               class="flex items-center gap-2 mb-4">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            <span class="text-xs font-black text-green-600 uppercase">En stock</span>
          </div>
          <div x-show="varianteSeleccionada ? !varianteSeleccionada.en_stock : !productoDetalle.en_stock"
               class="flex items-center gap-2 mb-4">
            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
            <span class="text-xs font-black text-red-500 uppercase">Sin stock</span>
          </div>

          <!-- Cantidad + Agregar -->
          <div x-show="varianteSeleccionada ? varianteSeleccionada.en_stock : productoDetalle.en_stock"
               class="flex gap-3 mb-4">
            <div class="flex items-center gap-2 bg-mt-cream rounded-xl px-4 py-3">
              <button @click="modalCantidad>1?modalCantidad--:null"
                      aria-label="Disminuir cantidad"
                      class="w-8 h-8 rounded-lg bg-white font-black text-mt-brown hover:bg-mt-orange hover:text-white transition-colors flex items-center justify-center shadow-sm text-lg">−</button>
              <span class="font-black text-mt-brown w-8 text-center text-lg" x-text="modalCantidad"></span>
              <button @click="modalCantidad++"
                      aria-label="Aumentar cantidad"
                      class="w-8 h-8 rounded-lg bg-white font-black text-mt-brown hover:bg-mt-orange hover:text-white transition-colors flex items-center justify-center shadow-sm text-lg">+</button>
            </div>
            <button @click="addToCartDetalle()"
                    class="flex-grow bg-mt-orange text-white py-3 rounded-xl font-black uppercase tracking-widest hover:bg-orange-500 transition-colors active:scale-95 shadow-lg text-sm">
              <i class="fas fa-shopping-basket mr-2"></i>Agregar al Carrito
            </button>
            <!-- Favorito -->
            <button @click="toggleFavorito(productoDetalle.id)"
                    :class="esFavorito(productoDetalle.id)?'bg-red-500 text-white':'bg-mt-cream text-mt-brown hover:bg-red-500 hover:text-white'"
                    class="p-3 rounded-xl transition-colors flex-shrink-0">
              <i class="fas fa-heart"></i>
            </button>
          </div>

          <!-- Sin stock: aviso -->
          <div x-show="!(varianteSeleccionada ? varianteSeleccionada.en_stock : productoDetalle.en_stock)"
               class="mb-4 space-y-3" x-data="{emailAviso:''}">
            <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-center">
              <p class="text-red-600 font-black text-sm uppercase">Producto sin stock</p>
            </div>
            <div class="flex gap-2">
              <input x-model="emailAviso" type="email" placeholder="Tu email para avisarte"
                     class="flex-grow px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange text-sm font-bold">
              <button @click="avisarStock(productoDetalle.id, emailAviso)"
                      class="px-4 py-3 bg-mt-brown text-white rounded-xl font-black text-sm hover:bg-mt-orange transition-colors whitespace-nowrap">
                Avísame
              </button>
            </div>
          </div>

          <!-- WhatsApp -->
          <a :href="'https://wa.me/56953793135?text=Hola!+Me+interesa:+'+encodeURIComponent(productoDetalle.nombre)"
             target="_blank"
             class="flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-[#0b3d26] text-[#0b3d26] font-black text-sm hover:bg-[#25D366] hover:text-[#0b3d26] hover:border-[#25D366] transition-colors">
            <i class="fab fa-whatsapp text-lg"></i> Consultar por WhatsApp
          </a>

        </div>
      </div>

      <!-- Descripcion completa -->
      <div x-show="productoDetalle.descripcion" class="bg-white rounded-[2rem] border border-mt-cream shadow-sm overflow-hidden mb-8">
        <button @click="descAbierta=!descAbierta"
                class="w-full flex items-center justify-between p-6 font-black text-mt-brown hover:bg-mt-cream transition-colors">
          <span class="text-lg">Descripción completa</span>
          <i class="fas transition-transform duration-200" :class="descAbierta?'fa-chevron-up text-mt-orange':'fa-chevron-down'"></i>
        </button>
        <div x-show="descAbierta"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="px-6 pb-6 border-t border-mt-cream pt-4 desc-content"
             x-html="productoDetalle.descripcion"></div>
      </div>

      <!-- Reviews -->
      <div class="bg-white rounded-[2rem] border border-mt-cream shadow-sm p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-black text-mt-brown uppercase">Reseñas</h3>
          <div x-show="reviewStats.total > 0" class="flex items-center gap-2">
            <div class="flex gap-0.5">
              <template x-for="i in 5" :key="i">
                <i :class="i<=Math.round(reviewStats.promedio)?'fas fa-star text-yellow-400':'far fa-star text-slate-300'"
                   class="text-base"></i>
              </template>
            </div>
            <span class="font-black text-mt-brown" x-text="Number(reviewStats.promedio).toFixed(1)"></span>
            <span class="text-slate-400 text-sm" x-text="'('+reviewStats.total+')'"></span>
          </div>
        </div>

        <!-- Lista reviews -->
        <div class="space-y-4 mb-8">
          <template x-for="r in reviews" :key="r.id">
            <div class="p-4 bg-slate-50 rounded-2xl">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 bg-mt-orange rounded-full flex items-center justify-center text-white font-black text-sm"
                       x-text="r.nombre.charAt(0).toUpperCase()"></div>
                  <span class="font-black text-mt-brown text-sm" x-text="r.nombre"></span>
                </div>
                <div class="flex gap-0.5">
                  <template x-for="i in 5" :key="i">
                    <i :class="i<=r.estrellas?'fas fa-star text-yellow-400':'far fa-star text-slate-300'"
                       class="text-xs"></i>
                  </template>
                </div>
              </div>
              <p class="text-sm text-slate-600" x-text="r.comentario"></p>
              <p class="text-xs text-slate-400 mt-1"
                 x-text="new Date(r.creado_en).toLocaleDateString('es-CL')"></p>
            </div>
          </template>
          <div x-show="reviews.length===0" class="text-center py-6 text-slate-400 italic text-sm">
            Sé el primero en dejar una reseña
          </div>
        </div>

        <!-- Formulario review -->
        <div x-data="{abierto:false, form:{nombre:'',email:'',estrellas:5,comentario:''},enviando:false,msg:''}">
          <button @click="abierto=!abierto"
                  class="w-full py-3 bg-mt-cream text-mt-brown rounded-2xl font-black text-sm uppercase hover:bg-mt-orange hover:text-white transition-colors">
            <i class="fas fa-pen mr-2"></i>Escribir reseña
          </button>
          <div x-show="abierto" class="mt-4 space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <input x-model="form.nombre" placeholder="Tu nombre *"
                     class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
              <input x-model="form.email" type="email" placeholder="Tu email *"
                     class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            </div>
            <!-- Estrellas -->
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-mt-brown">Calificación:</span>
              <div class="flex gap-1">
                <template x-for="i in 5" :key="i">
                  <button @click="form.estrellas=i" type="button">
                    <i :class="i<=form.estrellas?'fas fa-star text-yellow-400':'far fa-star text-slate-300'"
                       class="text-xl cursor-pointer hover:text-yellow-400 transition-colors"></i>
                  </button>
                </template>
              </div>
            </div>
            <textarea x-model="form.comentario" placeholder="Tu comentario..." rows="3"
                      class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange text-sm resize-none"></textarea>
            <p x-show="msg" x-text="msg" class="text-sm font-bold text-center"
               :class="msg.includes('Error')?'text-red-500':'text-green-600'"></p>
            <button @click="enviarReview(productoDetalle.id, form, (m)=>{msg=m;if(!m.includes('Error')){abierto=false}})"
                    :disabled="enviando"
                    class="w-full bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors disabled:opacity-60">
              Enviar Reseña
            </button>
          </div>
        </div>
      </div>

      <!-- Cross-selling -->
      <div x-show="productosRelacionados.length > 0" class="mb-8">
        <div class="flex items-center gap-4 mb-6">
          <h3 class="text-xl font-black text-mt-brown uppercase tracking-tighter whitespace-nowrap">También te puede interesar</h3>
          <div class="h-px flex-grow bg-mt-cream"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <template x-for="p in productosRelacionados" :key="p.id">
            <div @click="abrirProducto(p.id, true, p.slug)"
                 class="premium-card bg-white p-4 rounded-[2.5rem] border border-mt-cream cursor-pointer group flex flex-col justify-between shadow-sm relative overflow-hidden">
              <div class="relative mb-3">
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
              <h4 class="font-black text-mt-brown text-sm leading-tight mb-3 line-clamp-2 italic flex-grow hover:text-mt-orange transition-colors duration-200" x-text="p.nombre"></h4>
              <p class="font-black text-mt-brown mt-auto" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
            </div>
          </template>
        </div>
      </div>

    </div>
  </template>

  <!-- Error 404: Producto no encontrado -->
  <template x-if="productoDetalle && productoDetalle.error && !cargandoProducto">
    <div class="bg-white rounded-[3rem] p-16 text-center border border-mt-cream shadow-sm max-w-lg mx-auto my-10">
      <i class="fas fa-exclamation-circle text-5xl text-mt-orange mb-4"></i>
      <h3 class="text-xl font-black text-mt-brown uppercase mb-2">Producto no encontrado</h3>
      <p class="text-sm font-bold text-slate-500 italic mb-6">Lo sentimos, el producto solicitado no existe o no se encuentra activo en nuestro catálogo.</p>
      <button @click="page='tienda';cargarProductos()" class="bg-mt-orange text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-orange-500 transition-colors shadow-lg">
        Ver Catálogo
      </button>
    </div>
  </template>
</div>
