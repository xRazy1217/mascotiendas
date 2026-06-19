<div x-show="page==='pagina'" x-cloak class="fade-in max-w-3xl mx-auto">
  <div x-show="cargandoPagina" class="text-center py-20 text-mt-orange">
    <i class="fas fa-spinner fa-spin text-4xl"></i>
  </div>
  <template x-if="paginaEstatica && !paginaEstatica.error && !cargandoPagina">
    <div class="bg-white rounded-[2rem] border border-mt-cream p-8 shadow-sm">
      <h1 class="text-3xl font-black text-mt-brown italic mb-6" x-text="paginaEstatica.titulo"></h1>
      <div class="desc-content" x-html="paginaEstatica.contenido"></div>
    </div>
  </template>

  <!-- Error 404: Página no encontrada -->
  <template x-if="paginaEstatica && paginaEstatica.error && !cargandoPagina">
    <div class="bg-white rounded-[3rem] p-16 text-center border border-mt-cream shadow-sm max-w-lg mx-auto my-10">
      <i class="fas fa-exclamation-circle text-5xl text-mt-orange mb-4"></i>
      <h3 class="text-xl font-black text-mt-brown uppercase mb-2">Página no encontrada</h3>
      <p class="text-sm font-bold text-slate-500 italic mb-6">La página que buscas no existe o ha sido dada de baja.</p>
      <button @click="page='home'" class="bg-mt-orange text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-orange-500 transition-colors shadow-lg">
        Ir al Inicio
      </button>
    </div>
  </template>
</div>
