<div x-show="seccion==='campanas'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Campañas</h2>
    <button @click="abrirFormCampana(null)"
            class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
      <i class="fas fa-plus mr-1"></i> Nueva Campaña
    </button>
  </div>

  <div class="space-y-3">
    <template x-for="c in listaCampanas" :key="c.id">
      <div class="bg-white rounded-2xl shadow-sm border border-mt-cream p-4 flex items-center gap-4">
        <img :src="c.imagen_url||''" x-show="c.imagen_url" class="w-16 h-16 object-cover rounded-xl flex-shrink-0">
        <div class="flex-grow min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <p class="font-black text-mt-brown" x-text="c.nombre"></p>
            <span :class="c.activo=='1'?'bg-green-100 text-green-700':'bg-slate-100 text-slate-400'"
                  class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase"
                  x-text="c.activo=='1'?'Activa':'Inactiva'"></span>
          </div>
          <p class="text-xs text-slate-400 font-bold" x-text="c.titulo"></p>
          <p class="text-xs text-slate-400" x-show="c.fecha_ini"
             x-text="'Del '+c.fecha_ini+' al '+c.fecha_fin"></p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
          <button @click="toggleCampana(c)"
                  :class="c.activo=='1'?'bg-green-50 text-green-600':'bg-slate-100 text-slate-400'"
                  class="px-3 py-1.5 rounded-lg font-bold text-xs hover:opacity-80 transition-colors">
            <i :class="c.activo=='1'?'fas fa-toggle-on':'fas fa-toggle-off'"></i>
          </button>
          <button @click="abrirFormCampana(c)"
                  class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
            <i class="fas fa-edit"></i>
          </button>
          <button @click="eliminarCampana(c.id)"
                  class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </template>
    <div x-show="listaCampanas.length===0" class="text-center py-12 text-slate-400 italic">
      No hay campañas creadas
    </div>
  </div>

  <!-- Modal -->
  <div x-show="formCampana" x-cloak @click.self="formCampana=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2rem] w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-black text-mt-brown" x-text="formCampana?.id?'Editar Campaña':'Nueva Campaña'"></h3>
        <button @click="formCampana=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
      </div>
      <template x-if="formCampana">
        <div class="space-y-3">
          <input x-model="formCampana.nombre" placeholder="Nombre interno *"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <input x-model="formCampana.titulo" placeholder="Título del popup *"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <textarea x-model="formCampana.texto" placeholder="Texto del popup" rows="2"
                    class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm resize-none"></textarea>
          <input x-model="formCampana.imagen_url" placeholder="URL de imagen (opcional)"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <div class="grid grid-cols-2 gap-3">
            <input x-model="formCampana.btn_texto" placeholder="Texto del botón"
                   class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            <input x-model="formCampana.btn_url" placeholder="URL del botón"
                   class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>
          <div>
            <label class="text-xs font-black text-slate-400 uppercase">Activación</label>
            <select x-model="formCampana.activacion"
                    class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none font-bold text-sm bg-white mt-1">
              <option value="exit">Exit intent (al salir)</option>
              <option value="entrada">Al entrar al sitio</option>
              <option value="segundos">Después de X segundos</option>
            </select>
          </div>
          <div x-show="formCampana.activacion==='segundos'">
            <label class="text-xs font-black text-slate-400 uppercase">Segundos</label>
            <input x-model="formCampana.segundos" type="number" min="1" max="60"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-black text-slate-400 uppercase">Fecha inicio</label>
              <input x-model="formCampana.fecha_ini" type="date"
                     class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
            </div>
            <div>
              <label class="text-xs font-black text-slate-400 uppercase">Fecha fin</label>
              <input x-model="formCampana.fecha_fin" type="date"
                     class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
            </div>
          </div>
          <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
            <input type="checkbox" x-model="formCampana.activo" class="w-4 h-4 accent-mt-orange"> Activa
          </label>
          <div class="flex gap-3 pt-2">
            <button @click="guardarCampana()"
                    class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
              Guardar
            </button>
            <button @click="formCampana=null"
                    class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
