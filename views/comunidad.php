<div x-show="page==='comunidad'" x-cloak class="fade-in">
  <!-- Spinner cargando -->
  <div x-show="cargandoComunidadCliente" class="py-20 text-center">
    <i class="fas fa-spinner fa-spin text-4xl text-mt-orange mb-3"></i>
    <p class="font-bold text-mt-brown uppercase tracking-wider text-sm">Cargando comunidad...</p>
  </div>

  <div x-show="!cargandoComunidadCliente">
    <!-- Main Highlight (Hero) -->
    <template x-if="listaComunidadCliente.length > 0">
      <div class="relative w-full rounded-[3.5rem] overflow-hidden shadow-2xl mb-12 border-b-[12px] border-mt-brown min-h-[400px] flex items-center">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0">
          <img :src="getProductImage(listaComunidadCliente[0].imagen_url)" 
               :alt="listaComunidadCliente[0].titulo"
               class="w-full h-full object-cover">
          <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/55 to-black/35"></div>
        </div>
        
        <!-- Content -->
        <div class="relative z-10 w-full px-8 py-16 md:px-16 text-center lg:text-left text-white max-w-4xl space-y-6">
          <span x-show="listaComunidadCliente[0].fecha_evento"
                class="inline-flex items-center gap-2 bg-mt-orange text-white px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest mb-2 shadow-md">
            <i class="fas fa-calendar-alt"></i>
            <span x-text="new Date(listaComunidadCliente[0].fecha_evento + 'T00:00:00').toLocaleDateString('es-CL', {day:'numeric', month:'long', year:'numeric'})"></span>
          </span>
          <h1 class="text-4xl md:text-7xl font-black uppercase tracking-tighter italic leading-none" x-text="listaComunidadCliente[0].titulo"></h1>
          <p class="text-slate-200 text-sm md:text-base font-bold leading-relaxed max-w-2xl" x-text="listaComunidadCliente[0].descripcion"></p>
          <div class="pt-4">
            <a :href="listaComunidadCliente[0].enlace_url || 'https://wa.me/56953793135'" target="_blank"
               class="inline-flex items-center gap-2 bg-mt-orange text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-orange-500 transition-all shadow-xl active:scale-95">
              <span x-text="listaComunidadCliente[0].cta_texto || 'Participar ahora'"></span>
              <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
    </template>

    <!-- Grid of other items -->
    <template x-if="listaComunidadCliente.length > 1">
      <div>
        <div class="flex items-center gap-4 mb-8">
          <h3 class="text-xl font-black text-mt-brown uppercase tracking-tighter whitespace-nowrap">Más Eventos y Noticias</h3>
          <div class="h-px flex-grow bg-mt-cream"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
          <template x-for="item in listaComunidadCliente.slice(1)" :key="item.id">
            <div class="premium-card bg-white p-5 rounded-[2.5rem] border border-mt-cream flex flex-col justify-between shadow-sm relative overflow-hidden">
              <div class="relative mb-4">
                <div class="overflow-hidden rounded-[2rem] aspect-[16/10] w-full bg-mt-cream relative">
                  <img :src="getProductImage(item.imagen_url)" :alt="item.titulo"
                       class="premium-card-img w-full h-full object-cover">
                </div>
                <span x-show="item.fecha_evento"
                      class="absolute top-3 left-3 glass-effect text-mt-brown text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider z-10 shadow-sm"
                      x-text="new Date(item.fecha_evento + 'T00:00:00').toLocaleDateString('es-CL', {day:'numeric', month:'short'})"></span>
              </div>
              
              <div class="space-y-2 flex-grow">
                <h4 class="font-black text-mt-brown text-lg leading-tight italic line-clamp-2" x-text="item.titulo"></h4>
                <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed font-bold" x-text="item.descripcion"></p>
              </div>

              <div class="pt-5 mt-auto">
                <a :href="item.enlace_url || 'https://wa.me/56953793135'" target="_blank"
                   class="premium-btn w-full inline-flex justify-center items-center gap-2 bg-mt-brown text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-mt-orange transition-colors shadow-md">
                  <span x-text="item.cta_texto || 'Saber más'"></span>
                  <i class="fas fa-chevron-right text-[8px]"></i>
                </a>
              </div>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- Empty state -->
    <template x-if="listaComunidadCliente.length === 0">
      <div class="bg-white rounded-[3rem] p-16 text-center border border-mt-cream shadow-sm">
        <i class="fas fa-users text-5xl text-mt-cream mb-4"></i>
        <h1 class="text-xl font-black text-mt-brown uppercase mb-2">Comunidad Mascotiendas</h1>
        <p class="text-sm font-bold text-slate-500 italic max-w-md mx-auto">Pronto publicaremos eventos, actividades y novedades para ti y tu mascota en La Serena y Coquimbo.</p>
      </div>
    </template>
  </div>
</div>
