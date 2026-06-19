<div x-show="page==='blog'" x-cloak class="fade-in">
  <div class="flex items-center gap-4 mb-8">
    <h1 x-show="!blogPost" class="text-2xl font-black text-mt-brown uppercase tracking-tighter">Blog</h1>
    <span x-show="blogPost" class="text-2xl font-black text-mt-brown uppercase tracking-tighter" x-cloak>Blog</span>
    <div class="h-px flex-grow bg-mt-cream"></div>
  </div>

  <div x-show="!blogPost" class="grid md:grid-cols-3 gap-6">
    <template x-for="post in blogPosts" :key="post.id">
      <div @click="abrirPost(post.slug)"
           class="bg-white rounded-[2rem] border border-mt-cream overflow-hidden hover:shadow-lg transition-all cursor-pointer group">
        <img :src="getProductImage(post.imagen_portada||'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600')"
             class="w-full h-48 object-cover group-hover:scale-105 transition-transform">
        <div class="p-5">
          <p class="text-xs text-slate-600 font-bold mb-2"
             x-text="new Date(post.creado_en).toLocaleDateString('es-CL',{year:'numeric',month:'long',day:'numeric'})"></p>
          <h3 class="font-black text-mt-brown text-lg italic mb-2 line-clamp-2" x-text="post.titulo"></h3>
          <p class="text-sm text-slate-500 line-clamp-3" x-text="post.extracto"></p>
          <span class="mt-3 inline-block text-mt-brown group-hover:text-mt-orange transition-colors font-black text-xs uppercase tracking-widest">Leer mas →</span>
        </div>
      </div>
    </template>
    <div x-show="blogPosts.length===0" class="col-span-3 text-center py-16 text-slate-600 italic">
      <i class="fas fa-newspaper text-4xl mb-3 opacity-30"></i>
      <p class="font-bold">Pronto publicaremos articulos</p>
    </div>
  </div>

  <!-- Detalle post -->
  <div x-show="blogPost" class="max-w-3xl mx-auto">
    <button @click="blogPost=null; pushURL('blog')" class="flex items-center gap-2 text-mt-brown font-bold text-sm mb-6 hover:text-mt-orange transition-colors">
      <i class="fas fa-arrow-left"></i> Volver al blog
    </button>
    <template x-if="blogPost && !blogPost.error">
      <article class="bg-white rounded-[2rem] border border-mt-cream overflow-hidden">
        <img :src="getProductImage(blogPost.imagen_portada||'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=800')"
             class="w-full h-64 object-cover">
        <div class="p-8">
          <p class="text-xs text-slate-600 font-bold mb-3"
             x-text="new Date(blogPost.creado_en).toLocaleDateString('es-CL',{year:'numeric',month:'long',day:'numeric'})"></p>
          <h1 class="text-3xl font-black text-mt-brown italic mb-6" x-text="blogPost.titulo"></h1>
          <div class="desc-content" x-html="blogPost.contenido"></div>
        </div>
      </article>
    </template>

    <!-- Error 404: Artículo no encontrado -->
    <template x-if="blogPost && blogPost.error">
      <div class="bg-white rounded-[3rem] p-16 text-center border border-mt-cream shadow-sm max-w-lg mx-auto my-10">
        <i class="fas fa-exclamation-circle text-5xl text-mt-orange mb-4"></i>
        <h3 class="text-xl font-black text-mt-brown uppercase mb-2">Artículo no encontrado</h3>
        <p class="text-sm font-bold text-slate-500 italic mb-6">El artículo solicitado no existe o ha sido retirado de nuestro blog.</p>
        <button @click="blogPost=null; cargarBlog()" class="bg-mt-orange text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-orange-500 transition-colors shadow-lg">
          Volver al Blog
        </button>
      </div>
    </template>
  </div>
</div>
