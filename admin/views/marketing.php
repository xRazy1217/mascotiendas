<div x-show="seccion==='marketing'" x-cloak>
  <h2 class="text-2xl font-black text-mt-brown mb-6 uppercase">Marketing</h2>

  <div class="grid lg:grid-cols-2 gap-6">

    <!-- Newsletter -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-mt-cream">
      <div class="flex items-center justify-between mb-4">
        <p class="font-black text-mt-brown">Suscriptores Newsletter</p>
        <span class="px-3 py-1 bg-mt-cream text-mt-brown rounded-full text-xs font-black"
              x-text="listaNewsletter.length+' suscriptores'"></span>
      </div>
      <div class="max-h-64 overflow-y-auto space-y-2 mb-4">
        <template x-for="s in listaNewsletter" :key="s.id">
          <div class="flex items-center justify-between p-2 bg-slate-50 rounded-xl">
            <span class="text-sm font-bold text-mt-brown" x-text="s.email"></span>
            <span class="text-xs text-slate-400" x-text="new Date(s.creado_en).toLocaleDateString('es-CL')"></span>
          </div>
        </template>
        <template x-if="listaNewsletter.length===0">
          <p class="text-slate-400 italic text-sm text-center py-4">Sin suscriptores aún</p>
        </template>
      </div>
      <button @click="exportarNewsletter()"
              class="w-full py-2 bg-mt-cream text-mt-brown rounded-xl font-bold text-sm hover:bg-mt-orange hover:text-white transition-colors">
        <i class="fas fa-download mr-2"></i>Exportar CSV
      </button>
    </div>

    <!-- Estadísticas marketing -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-mt-cream">
      <p class="font-black text-mt-brown mb-4">Resumen</p>
      <div class="space-y-4">
        <div class="flex items-center justify-between p-3 bg-mt-cream rounded-xl">
          <div class="flex items-center gap-3">
            <i class="fas fa-envelope text-mt-orange"></i>
            <span class="font-bold text-mt-brown text-sm">Suscriptores activos</span>
          </div>
          <span class="font-black text-mt-brown" x-text="listaNewsletter.length"></span>
        </div>
        <div class="flex items-center justify-between p-3 bg-mt-cream rounded-xl">
          <div class="flex items-center gap-3">
            <i class="fas fa-tag text-mt-orange"></i>
            <span class="font-bold text-mt-brown text-sm">Cupones activos</span>
          </div>
          <span class="font-black text-mt-brown" x-text="listaCupones.filter(c=>c.activo).length"></span>
        </div>
        <div class="flex items-center justify-between p-3 bg-mt-cream rounded-xl">
          <div class="flex items-center gap-3">
            <i class="fas fa-users text-mt-orange"></i>
            <span class="font-bold text-mt-brown text-sm">Clientes registrados</span>
          </div>
          <span class="font-black text-mt-brown" x-text="stats.usuarios||0"></span>
        </div>
      </div>

      <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
        <p class="text-xs font-black text-yellow-700 uppercase mb-1">Próximamente</p>
        <p class="text-xs text-yellow-600">Envío de emails masivos via SMTP, campañas automáticas y segmentación de clientes.</p>
      </div>
    </div>
  </div>
</div>
