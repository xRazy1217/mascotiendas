<div x-show="seccion==='textos'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Textos Mágicos</h2>
    <span class="text-xs text-slate-400 font-bold">Edita el contenido de la tienda</span>
  </div>

  <!-- Hero -->
  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream p-5 mb-4">
    <p class="font-black text-mt-brown text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
      <i class="fas fa-star text-mt-orange"></i> Hero Principal
    </p>
    <div class="space-y-3">
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Título</label>
        <input x-model="textos.hero_titulo" @change="guardarTexto('hero_titulo', textos.hero_titulo)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Subtítulo</label>
        <input x-model="textos.hero_subtitulo" @change="guardarTexto('hero_subtitulo', textos.hero_subtitulo)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">URL Imagen</label>
        <input x-model="textos.hero_imagen" @change="guardarTexto('hero_imagen', textos.hero_imagen)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
        <img :src="textos.hero_imagen" class="mt-2 h-24 rounded-xl object-cover" x-show="textos.hero_imagen">
      </div>
    </div>
  </div>

  <!-- Banner Delivery -->
  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream p-5 mb-4">
    <p class="font-black text-mt-brown text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
      <i class="fas fa-truck text-mt-orange"></i> Banner Delivery
    </p>
    <div class="space-y-3">
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Título</label>
        <input x-model="textos.delivery_titulo" @change="guardarTexto('delivery_titulo', textos.delivery_titulo)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Texto</label>
        <textarea x-model="textos.delivery_texto" @change="guardarTexto('delivery_texto', textos.delivery_texto)"
                  rows="2" class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1 resize-none"></textarea>
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">URL Imagen</label>
        <input x-model="textos.delivery_imagen" @change="guardarTexto('delivery_imagen', textos.delivery_imagen)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
        <img :src="textos.delivery_imagen" class="mt-2 h-24 rounded-xl object-cover" x-show="textos.delivery_imagen">
      </div>
    </div>
  </div>

  <!-- Info items -->
  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream p-5 mb-4">
    <p class="font-black text-mt-brown text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
      <i class="fas fa-info-circle text-mt-orange"></i> Íconos Informativos
    </p>
    <div class="grid grid-cols-2 gap-3">
      <template x-for="i in [1,2,3,4]" :key="i">
        <div>
          <label class="text-xs font-black text-slate-400 uppercase" x-text="'Item '+i"></label>
          <input :x-model="'textos.info_item'+i"
                 :value="textos['info_item'+i]"
                 @change="guardarTexto('info_item'+i, $event.target.value)"
                 class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
        </div>
      </template>
    </div>
  </div>

  <!-- Footer -->
  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream p-5">
    <p class="font-black text-mt-brown text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
      <i class="fas fa-shoe-prints text-mt-orange"></i> Footer
    </p>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Email</label>
        <input x-model="textos.footer_email" @change="guardarTexto('footer_email', textos.footer_email)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Teléfono</label>
        <input x-model="textos.footer_telefono" @change="guardarTexto('footer_telefono', textos.footer_telefono)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div class="col-span-2">
        <label class="text-xs font-black text-slate-400 uppercase">Dirección</label>
        <input x-model="textos.footer_direccion" @change="guardarTexto('footer_direccion', textos.footer_direccion)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Instagram URL</label>
        <input x-model="textos.footer_instagram" @change="guardarTexto('footer_instagram', textos.footer_instagram)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
      <div>
        <label class="text-xs font-black text-slate-400 uppercase">Facebook URL</label>
        <input x-model="textos.footer_facebook" @change="guardarTexto('footer_facebook', textos.footer_facebook)"
               class="w-full px-4 py-2 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm mt-1">
      </div>
    </div>
  </div>
</div>
