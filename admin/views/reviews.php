<div x-show="seccion==='reviews'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Reseñas</h2>
    <div class="flex gap-2">
      <button @click="reviewFiltro=-1;cargarReviewsAdmin()"
              :class="reviewFiltro===-1?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
              class="px-3 py-1.5 rounded-xl font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">Todas</button>
      <button @click="reviewFiltro=0;cargarReviewsAdmin()"
              :class="reviewFiltro===0?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
              class="px-3 py-1.5 rounded-xl font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">Pendientes</button>
      <button @click="reviewFiltro=1;cargarReviewsAdmin()"
              :class="reviewFiltro===1?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
              class="px-3 py-1.5 rounded-xl font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">Aprobadas</button>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Reseña</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Producto</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Estado</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="r in listaReviews" :key="r.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3">
              <div class="flex gap-0.5 mb-1">
                <template x-for="i in 5" :key="i">
                  <i :class="i<=r.estrellas?'fas fa-star text-yellow-400':'far fa-star text-slate-300'" class="text-xs"></i>
                </template>
              </div>
              <p class="font-bold text-mt-brown text-sm" x-text="r.nombre"></p>
              <p class="text-xs text-slate-500 line-clamp-2" x-text="r.comentario"></p>
              <p class="text-xs text-slate-400 mt-1" x-text="new Date(r.creado_en).toLocaleDateString('es-CL')"></p>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <p class="text-xs font-bold text-mt-brown line-clamp-2" x-text="r.producto_nombre"></p>
            </td>
            <td class="px-4 py-3">
              <span :class="r.aprobado?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700'"
                    class="px-2 py-0.5 rounded-full text-xs font-black uppercase"
                    x-text="r.aprobado?'Aprobada':'Pendiente'"></span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button x-show="!r.aprobado" @click="aprobarReview(r.id,1)"
                        class="px-3 py-1.5 bg-green-50 text-green-600 rounded-lg font-bold text-xs hover:bg-green-500 hover:text-white transition-colors">
                  <i class="fas fa-check"></i>
                </button>
                <button x-show="r.aprobado" @click="aprobarReview(r.id,0)"
                        class="px-3 py-1.5 bg-yellow-50 text-yellow-600 rounded-lg font-bold text-xs hover:bg-yellow-500 hover:text-white transition-colors">
                  <i class="fas fa-eye-slash"></i>
                </button>
                <button @click="eliminarReview(r.id)"
                        class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaReviews.length===0" class="text-center py-12 text-slate-400 italic">No hay reseñas</div>
  </div>
</div>
