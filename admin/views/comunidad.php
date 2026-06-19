<div x-show="seccion==='comunidad'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Comunidad y Eventos</h2>
    <button @click="abrirFormComunidad(null)"
            class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
      <i class="fas fa-plus mr-1"></i> Nuevo Contenido
    </button>
  </div>

  <!-- Buscador rápido local -->
  <div class="flex gap-3 mb-4">
    <input x-model="comQ" placeholder="Filtrar por título..."
           class="flex-grow max-w-md px-4 py-2 rounded-xl border border-mt-cream focus:outline-none text-sm font-bold">
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase w-16">ID</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Título</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Fecha Evento</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">CTA (Texto/Enlace)</th>
          <th class="text-center px-4 py-3 font-black text-mt-brown text-xs uppercase w-32">Estado</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase w-28">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="item in listaComunidad.filter(c => c.titulo.toLowerCase().includes(comQ.toLowerCase()))" :key="item.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3 font-black text-mt-brown" x-text="'#'+item.id"></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img :src="getProductImage(item.imagen_url)" class="w-12 h-10 object-cover rounded-lg flex-shrink-0 bg-slate-50 border border-mt-cream">
                <div>
                  <span class="font-bold text-mt-brown text-sm block" x-text="item.titulo"></span>
                  <span class="text-xs text-slate-400 line-clamp-1" x-text="item.descripcion"></span>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-xs text-mt-brown font-bold hidden md:table-cell" x-text="item.fecha_evento ? new Date(item.fecha_evento + 'T00:00:00').toLocaleDateString('es-CL') : 'Sin fecha'"></td>
            <td class="px-4 py-3 text-xs hidden md:table-cell">
              <div class="font-bold text-mt-brown" x-text="item.cta_texto || 'Participar ahora'"></div>
              <div class="text-slate-400 truncate max-w-xs" x-text="item.enlace_url || 'Sin enlace'"></div>
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleActivoComunidad(item)"
                      :class="(item.activo === 1 || item.activo === '1') ? 'bg-[#25D366] text-[#0b3d26]' : 'bg-slate-200 text-slate-500'"
                      class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider transition-colors inline-flex items-center gap-1.5 shadow-sm">
                <i class="fas" :class="(item.activo === 1 || item.activo === '1') ? 'fa-check-circle' : 'fa-times-circle'"></i>
                <span x-text="(item.activo === 1 || item.activo === '1') ? 'Activo' : 'Inactivo'"></span>
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="abrirFormComunidad(item)"
                        class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="eliminarComunidad(item.id)"
                        class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaComunidad.length===0" class="text-center py-12 text-slate-400 italic">
      No hay contenidos de comunidad
    </div>
  </div>

  <!-- Modal Formulario Comunidad -->
  <div x-show="formComunidad" x-cloak @click.self="formComunidad=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
      <div class="bg-white rounded-[2rem] w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-black text-mt-brown" x-text="formComunidad?.id?'Editar Contenido de Comunidad':'Nuevo Contenido de Comunidad'"></h3>
          <button @click="formComunidad=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>

        <template x-if="formComunidad">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Título *</label>
              <input x-model="formComunidad.titulo" required placeholder="Ej: Corridas Caninas 2026"
                     class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            </div>

            <div>
              <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Descripción / Contenido *</label>
              <textarea x-model="formComunidad.descripcion" required placeholder="Describe el evento o noticia para la comunidad..." rows="4"
                        class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Texto del Botón (CTA)</label>
                <input x-model="formComunidad.cta_texto" placeholder="Ej: Participar ahora"
                       class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
              </div>
              <div>
                <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Enlace del Botón (URL)</label>
                <input x-model="formComunidad.enlace_url" placeholder="Ej: https://wa.me/..."
                       class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Fecha del Evento (opcional)</label>
                <input type="date" x-model="formComunidad.fecha_evento"
                       class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm bg-white text-mt-brown">
              </div>
              <div class="flex items-end pb-3">
                <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
                  <input type="checkbox" x-model="formComunidad.activo" class="w-4 h-4 accent-mt-orange">
                  <span>Activo / Publicado</span>
                </label>
              </div>
            </div>

            <!-- Imagen -->
            <div>
              <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-2">Imagen de Portada</label>
              <div class="flex items-center gap-4">
                <img :src="getProductImage(formComunidad.imagen_url)" class="w-20 h-16 object-cover rounded-xl bg-slate-50 border border-mt-cream flex-shrink-0">
                <div class="space-y-2 flex-grow">
                  <div class="flex gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 bg-mt-cream text-mt-brown rounded-xl cursor-pointer hover:bg-mt-orange hover:text-white transition-colors font-bold text-xs">
                      <i class="fas fa-upload"></i> Subir
                      <input type="file" accept="image/*" class="hidden" @change="subirImagenComunidad($event)">
                    </label>
                    <button @click="eliminarImagenComunidad()" x-show="formComunidad.imagen_url" type="button"
                            class="px-3 py-2 bg-red-50 text-red-500 rounded-xl font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                      Eliminar
                    </button>
                  </div>
                  <input x-model="formComunidad.imagen_url" placeholder="URL directa de imagen (opcional)"
                         class="w-full px-3 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-xs">
                </div>
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <button @click="guardarComunidad()"
                      class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
                Guardar
              </button>
              <button @click="formComunidad=null"
                      class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </template>
      </div>
  </div>
</div>
