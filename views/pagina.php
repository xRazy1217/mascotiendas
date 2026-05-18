<div x-show="page==='pagina'" x-cloak class="fade-in max-w-3xl mx-auto">
  <div x-show="cargandoPagina" class="text-center py-20 text-mt-orange">
    <i class="fas fa-spinner fa-spin text-4xl"></i>
  </div>
  <template x-if="paginaEstatica && !cargandoPagina">
    <div class="bg-white rounded-[2rem] border border-mt-cream p-8 shadow-sm">
      <h1 class="text-3xl font-black text-mt-brown italic mb-6" x-text="paginaEstatica.titulo"></h1>
      <div class="desc-content" x-html="paginaEstatica.contenido"></div>
    </div>
  </template>
</div>
