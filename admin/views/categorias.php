<div x-show="seccion==='categorias'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Categorías</h2>
    <button @click="abrirFormCategoria(null)"
            class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
      <i class="fas fa-plus mr-1"></i> Nueva
    </button>
  </div>

  <!-- Buscador rápido local -->
  <div class="flex gap-3 mb-4">
    <input x-model="catQ" placeholder="Filtrar por nombre..."
           class="flex-grow max-w-md px-4 py-2 rounded-xl border border-mt-cream focus:outline-none text-sm font-bold">
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase w-16">ID</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Categoría</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Enlace (Slug)</th>
          <th class="text-center px-4 py-3 font-black text-mt-brown text-xs uppercase w-32">Productos</th>
          <th class="text-center px-4 py-3 font-black text-mt-brown text-xs uppercase w-40">Explora en Inicio</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase w-28">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="cat in listaCategorias.filter(c => c.nombre.toLowerCase().includes(catQ.toLowerCase()))" :key="cat.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3 font-black text-mt-brown" x-text="'#'+cat.id"></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img :src="getProductImage(cat.imagen_url)" class="w-10 h-10 object-cover rounded-lg flex-shrink-0 bg-slate-50 border border-mt-cream">
                <span class="font-bold text-mt-brown text-sm" x-text="cat.nombre"></span>
              </div>
            </td>
            <td class="px-4 py-3 text-xs text-slate-400 font-bold" x-text="cat.slug"></td>
            <td class="px-4 py-3 text-center font-bold text-mt-brown" x-text="cat.total_productos || 0"></td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleMostrarHome(cat)"
                      :class="(cat.mostrar_home === 1 || cat.mostrar_home === '1') ? 'bg-[#25D366] text-[#0b3d26]' : 'bg-slate-200 text-slate-500'"
                      class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider transition-colors inline-flex items-center gap-1.5 shadow-sm">
                <i class="fas" :class="(cat.mostrar_home === 1 || cat.mostrar_home === '1') ? 'fa-check-circle' : 'fa-times-circle'"></i>
                <span x-text="(cat.mostrar_home === 1 || cat.mostrar_home === '1') ? 'Sí' : 'No'"></span>
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="abrirFormCategoria(cat)"
                        class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="eliminarCategoria(cat.id)"
                        :disabled="cat.slug === 'farmacia-mascotas'"
                        :class="cat.slug === 'farmacia-mascotas' ? 'opacity-40 cursor-not-allowed' : 'hover:bg-red-500 hover:text-white'"
                        class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaCategorias.length===0" class="text-center py-12 text-slate-400 italic">
      Cargando categorías...
    </div>
  </div>

  <!-- Modal Formulario Categoría -->
  <div x-show="formCategoria" x-cloak @click.self="formCategoria=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2rem] w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-black text-mt-brown" x-text="formCategoria?.id?'Editar Categoría':'Nueva Categoría'"></h3>
        <button @click="formCategoria=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
      </div>

      <template x-if="formCategoria">
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Nombre *</label>
            <input x-model="formCategoria.nombre" required placeholder="Ej: Snacks Perro"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>
          <div>
            <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-1">Enlace / Slug (opcional)</label>
            <input x-model="formCategoria.slug" placeholder="Ej: snacks-perro (se autogenera si se deja en blanco)"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>

          <!-- Imagen de la categoría -->
          <div>
            <label class="block text-xs font-black text-mt-brown uppercase tracking-wider mb-2">Imagen de la Categoría</label>
            <div class="flex items-center gap-4">
              <img :src="getProductImage(formCategoria.imagen_url)" class="w-16 h-16 object-cover rounded-xl bg-slate-50 border border-mt-cream flex-shrink-0">
              <div class="space-y-2 flex-grow">
                <div class="flex gap-2">
                  <label class="flex items-center gap-2 px-3 py-2 bg-mt-cream text-mt-brown rounded-xl cursor-pointer hover:bg-mt-orange hover:text-white transition-colors font-bold text-xs">
                    <i class="fas fa-upload"></i> Subir
                    <input type="file" accept="image/*" class="hidden" @change="subirImagenCategoria($event)">
                  </label>
                  <button @click="eliminarImagenCategoria()" x-show="formCategoria.imagen_url" type="button"
                          class="px-3 py-2 bg-red-50 text-red-500 rounded-xl font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                    Eliminar
                  </button>
                </div>
                <input x-model="formCategoria.imagen_url" placeholder="URL directa de imagen (opcional)"
                       class="w-full px-3 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-xs">
              </div>
            </div>
          </div>

          <div class="bg-mt-cream rounded-2xl p-4 space-y-3">
            <p class="font-black text-mt-brown text-xs uppercase tracking-widest">Ajustes SEO</p>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Meta Título (opcional)</label>
              <input x-model="formCategoria.meta_titulo" placeholder="Título SEO de la página"
                     class="w-full px-3 py-2.5 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 mb-1">Meta Descripción (opcional)</label>
              <textarea x-model="formCategoria.meta_descripcion" placeholder="Descripción SEO para buscadores" rows="2"
                        class="w-full px-3 py-2.5 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm resize-none"></textarea>
            </div>
          </div>

          <label class="flex items-center gap-2 font-bold text-sm cursor-pointer p-1">
            <input type="checkbox" x-model="formCategoria.mostrar_home" class="w-4 h-4 accent-mt-orange">
            <span>Mostrar en la sección "Explora por Categoría" de la página de inicio</span>
          </label>

          <div class="flex gap-3 pt-2">
            <button @click="guardarCategoria()"
                    class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
              Guardar
            </button>
            <button @click="formCategoria=null"
                    class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
