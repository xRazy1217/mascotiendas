<div x-show="seccion==='blog'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Blog</h2>
    <button @click="abrirFormPost(null)"
            class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
      <i class="fas fa-plus mr-1"></i> Nuevo Post
    </button>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Post</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Estado</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Fecha</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="p in listaBlog" :key="p.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img :src="p.imagen_portada||'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=80'"
                     class="w-12 h-12 object-cover rounded-xl flex-shrink-0">
                <div>
                  <p class="font-bold text-mt-brown line-clamp-1" x-text="p.titulo"></p>
                  <p class="text-xs text-slate-400" x-text="'/'+p.slug"></p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span :class="p.publicado ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                    class="px-2 py-0.5 rounded-full text-xs font-black uppercase"
                    x-text="p.publicado ? 'Publicado' : 'Borrador'"></span>
            </td>
            <td class="px-4 py-3 text-xs text-slate-400 hidden md:table-cell"
                x-text="new Date(p.creado_en).toLocaleDateString('es-CL')"></td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="abrirFormPost(p)"
                        class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="eliminarPost(p.id)"
                        class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaBlog.length===0" class="text-center py-12 text-slate-400 italic">
      No hay posts aún
    </div>
  </div>

  <!-- Modal post -->
  <div x-show="formPost" x-cloak @click.self="formPost=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2rem] w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-black text-mt-brown" x-text="formPost?.id ? 'Editar Post' : 'Nuevo Post'"></h3>
        <button @click="formPost=null" class="text-slate-400 hover:text-slate-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <template x-if="formPost">
        <div class="space-y-4">
          <input x-model="formPost.titulo" placeholder="Título del post *"
                 @input="if(!formPost.id) formPost.slug = slugify(formPost.titulo)"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <div class="flex gap-3">
            <input x-model="formPost.slug" placeholder="slug-del-post"
                   class="flex-grow px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm text-slate-500">
          </div>
          <input x-model="formPost.imagen_portada" placeholder="URL imagen de portada"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <div x-show="formPost.imagen_portada" class="rounded-xl overflow-hidden h-32">
            <img :src="formPost.imagen_portada" class="w-full h-full object-cover">
          </div>
          <textarea x-model="formPost.extracto" placeholder="Extracto / resumen corto" rows="2"
                    class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm resize-none"></textarea>
          <textarea x-model="formPost.contenido" placeholder="Contenido completo (acepta HTML)" rows="10"
                    class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange text-sm resize-none font-mono"></textarea>

          <!-- SEO -->
          <div class="border border-mt-cream rounded-xl p-4 space-y-3">
            <p class="font-black text-mt-brown text-xs uppercase tracking-widest">SEO</p>
            <input x-model="formPost.meta_titulo" placeholder="Meta título (max 60 caracteres)"
                   class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange text-sm font-bold">
            <textarea x-model="formPost.meta_descripcion" placeholder="Meta descripción (max 160 caracteres)" rows="2"
                      class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange text-sm resize-none"></textarea>
          </div>

          <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
            <input type="checkbox" x-model="formPost.publicado" class="w-4 h-4 accent-mt-orange">
            Publicar (visible en el sitio)
          </label>

          <div class="flex gap-3 pt-2">
            <button @click="guardarPost()"
                    class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
              Guardar
            </button>
            <button @click="formPost=null"
                    class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
