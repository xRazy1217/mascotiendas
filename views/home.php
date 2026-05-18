<div x-show="page==='home'" x-cloak class="fade-in">

  <!-- Hero -->
  <div class="relative w-full rounded-[3rem] overflow-hidden shadow-2xl mb-10 border-b-8 border-mt-orange"
       style="background:radial-gradient(circle at top right,#8b5d3e,#7F5234)">
    <div class="flex flex-col lg:flex-row items-center min-h-[420px] px-8 py-12 md:px-16 text-white text-center lg:text-left">
      <div class="w-full lg:w-1/2 space-y-5">
        <div class="inline-flex items-center gap-2 bg-white/10 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest">
          <i class="fas fa-truck text-mt-orange"></i> Delivery gratis en La Serena y Coquimbo
        </div>
        <h2 class="text-5xl md:text-7xl font-black leading-none uppercase tracking-tighter">
          <span x-text="textos.hero_titulo||'AMOR EN CADA BOCADO.'"></span>
        </h2>
        <p class="text-white/70 text-base font-medium" x-text="textos.hero_subtitulo||'Comida para perros y gatos · Arena sanitaria · Farmacia veterinaria'"></p>
        <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
          <button @click="page='tienda';cargarProductos()"
                  class="bg-mt-orange text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:bg-orange-500 transition-colors">
            Ver Catálogo
          </button>
          <a href="https://wa.me/56953793135" target="_blank"
             class="bg-[#25D366] text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:bg-green-500 transition-colors flex items-center gap-2">
            <i class="fab fa-whatsapp text-lg"></i> Pedir por WhatsApp
          </a>
        </div>
        <p class="text-white/50 text-xs font-bold italic">
          Sucursal activa: <span class="text-white font-black" x-text="sucursalNombre()"></span>
        </p>
      </div>
      <div class="w-full lg:w-1/2 flex justify-center mt-10 lg:mt-0" x-data="{slide:0}" x-init="setInterval(()=>slide=(slide+1)%3, 4000)">
        <div class="relative w-64 md:w-80 h-64 md:h-80 rounded-b-full overflow-hidden shadow-2xl">
          <img src="https://images.unsplash.com/photo-1537151608828-ea2b11777ee8?auto=format&fit=crop&w=500&h=500"
               class="float-anim absolute inset-0 w-full h-full object-cover transition-opacity duration-700"
               :class="slide===0?'opacity-100':'opacity-0'">
          <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=500&h=500"
               class="float-anim absolute inset-0 w-full h-full object-cover transition-opacity duration-700"
               :class="slide===1?'opacity-100':'opacity-0'">
          <img src="https://images.unsplash.com/photo-1425082661705-1834bfd09dca?auto=format&fit=crop&w=500&h=500"
               class="float-anim absolute inset-0 w-full h-full object-cover transition-opacity duration-700"
               :class="slide===2?'opacity-100':'opacity-0'">
        </div>
      </div>
    </div>
  </div>

  <!-- Banner delivery -->
  <div class="relative rounded-[2rem] overflow-hidden shadow-xl mb-10 border-b-4 border-mt-orange">
    <!-- Layout: imagen derecha, texto izquierda -->
    <div class="flex flex-col md:flex-row bg-mt-brown">
      <!-- Texto -->
      <div class="flex-1 p-8 md:p-10 flex flex-col justify-center">
        <span class="inline-flex items-center gap-2 bg-mt-orange/20 text-mt-orange px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest mb-4 w-fit">
          <i class="fas fa-truck"></i> Importante
        </span>
        <h3 class="text-xl md:text-2xl font-black text-white uppercase leading-snug mb-3"
            x-text="textos.delivery_titulo||'Nuestro delivery es exclusivo en la conurbación La Serena – Coquimbo'"></h3>
        <p class="text-white/60 text-sm font-bold mb-6 leading-relaxed"
           x-text="textos.delivery_texto||'Delivery gratis en todo La Serena y Coquimbo zona urbana.'"></p>
        <a href="https://wa.me/56953793135?text=Hola!+Quiero+verificar+si+mi+direccion+tiene+cobertura+de+delivery"
           target="_blank"
           class="inline-flex items-center gap-2 bg-[#25D366] text-white px-6 py-3 rounded-2xl font-black text-sm hover:bg-green-500 transition-colors shadow-lg w-fit">
          <i class="fab fa-whatsapp text-lg"></i> Verificar cobertura
        </a>
      </div>
      <!-- Imagen -->
      <div class="w-full md:w-72 lg:w-80 flex-shrink-0">
        <img :src="textos.delivery_imagen||'https://mascotiendas.cl/wp-content/uploads/2025/02/IMG-20250130-WA0049-768x768.jpg'"
             alt="Delivery Mascotiendas" class="w-full h-64 md:h-full object-cover">
      </div>
    </div>
  </div>

  <!-- Info tienda -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
    <div class="bg-white p-4 rounded-2xl border border-mt-cream text-center shadow-sm">
      <i class="fas fa-paw text-mt-orange text-2xl mb-2"></i>
      <p class="font-black text-mt-brown text-xs uppercase">Comida Perros y Gatos</p>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-mt-cream text-center shadow-sm">
      <i class="fas fa-briefcase-medical text-mt-orange text-2xl mb-2"></i>
      <p class="font-black text-mt-brown text-xs uppercase">Farmacia Veterinaria</p>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-mt-cream text-center shadow-sm">
      <i class="fas fa-truck text-mt-orange text-2xl mb-2"></i>
      <p class="font-black text-mt-brown text-xs uppercase">Delivery Gratis</p>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-mt-cream text-center shadow-sm">
      <i class="fas fa-phone text-mt-orange text-2xl mb-2"></i>
      <p class="font-black text-mt-brown text-xs uppercase">+569 5379 3135</p>
    </div>
  </div>

  <!-- Categorías principales -->
  <div class="flex items-center gap-4 mb-6">
    <h3 class="text-xl font-black text-mt-brown uppercase tracking-tighter whitespace-nowrap">Explora por Categoría</h3>
    <div class="h-px flex-grow bg-mt-cream"></div>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
    <div class="group relative h-48 rounded-[2rem] overflow-hidden cursor-pointer shadow-lg"
         @click="filtroQ='perro';filtroCategoria='comida-perros';page='tienda';cargarProductos()">
      <img src="https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=400"
           class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
      <div class="absolute inset-0 bg-gradient-to-t from-mt-brown/90 to-transparent flex items-end p-5">
        <div>
          <i class="fas fa-dog text-mt-orange mb-1 text-lg"></i>
          <h4 class="text-white text-lg font-black uppercase italic tracking-tighter leading-tight">Comida<br>Perros</h4>
        </div>
      </div>
    </div>

    <div class="group relative h-48 rounded-[2rem] overflow-hidden cursor-pointer shadow-lg"
         @click="filtroQ='gato';filtroCategoria='comida-gatos';page='tienda';cargarProductos()">
      <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=400"
           class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
      <div class="absolute inset-0 bg-gradient-to-t from-mt-orange/90 to-transparent flex items-end p-5">
        <div>
          <i class="fas fa-cat text-white mb-1 text-lg"></i>
          <h4 class="text-white text-lg font-black uppercase italic tracking-tighter leading-tight">Comida<br>Gatos</h4>
        </div>
      </div>
    </div>

    <div class="group relative h-48 rounded-[2rem] overflow-hidden cursor-pointer shadow-lg"
         @click="filtroCategoria='arena-gatos';page='tienda';cargarProductos()">
      <img src="https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=400"
           class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
      <div class="absolute inset-0 bg-gradient-to-t from-slate-800/90 to-transparent flex items-end p-5">
        <div>
          <i class="fas fa-box text-mt-orange mb-1 text-lg"></i>
          <h4 class="text-white text-lg font-black uppercase italic tracking-tighter leading-tight">Arena<br>Sanitaria</h4>
        </div>
      </div>
    </div>

    <div class="group relative h-48 rounded-[2rem] overflow-hidden cursor-pointer shadow-lg"
         @click="page='farmacia';cargarProductos()">
      <img src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=400"
           class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
      <div class="absolute inset-0 bg-gradient-to-t from-mt-brown/90 to-transparent flex items-end p-5">
        <div>
          <i class="fas fa-briefcase-medical text-mt-orange mb-1 text-lg"></i>
          <h4 class="text-white text-lg font-black uppercase italic tracking-tighter leading-tight">Farmacia<br>Veterinaria</h4>
        </div>
      </div>
    </div>
  </div>

  <!-- Destacados -->
  <div class="flex items-center gap-4 mb-6">
    <h3 class="text-xl font-black text-mt-brown uppercase tracking-tighter whitespace-nowrap">Ofertas de la Semana</h3>
    <div class="h-px flex-grow bg-mt-cream"></div>
    <button @click="page='tienda';cargarProductos()" class="text-xs font-black text-mt-orange uppercase tracking-widest whitespace-nowrap hover:underline">
      Ver todo →
    </button>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 gap-5 mb-12">
    <template x-for="p in destacados" :key="p.id">
      <div @click="abrirProducto(p.id)"
           class="bg-white p-5 rounded-[2rem] border border-mt-cream hover:shadow-lg transition-all cursor-pointer group flex flex-col">
        <div class="relative mb-4">
          <img :src="p.imagen||'/mascotiendas/assets/no-image.png'" :alt="p.nombre"
               class="w-full h-48 object-cover rounded-2xl group-hover:scale-105 transition-transform">
          <span x-show="!p.en_stock"
                class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Sin stock</span>
        </div>
        <h4 class="font-bold text-mt-brown text-sm mb-4 line-clamp-2 italic flex-grow" x-text="p.nombre"></h4>
        <div class="flex items-center justify-between mt-auto">
          <div>
            <template x-if="p.precio_rebajado">
              <span class="text-xs line-through text-slate-400 block" x-text="formatPrecio(p.precio_normal)"></span>
            </template>
            <p class="font-black text-lg text-mt-brown" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
          </div>
          <button @click.stop="addToCart(p)" :disabled="!p.en_stock"
                  class="bg-mt-orange text-white p-2.5 rounded-xl hover:bg-orange-500 transition-colors active:scale-90 disabled:opacity-40">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>
    </template>
  </div>

  <!-- Blog preview -->
  <div class="flex items-center gap-4 mb-6">
    <h3 class="text-xl font-black text-mt-brown uppercase tracking-tighter whitespace-nowrap">Blog & Consejos</h3>
    <div class="h-px flex-grow bg-mt-cream"></div>
    <button @click="page='blog';cargarBlog()" class="text-xs font-black text-mt-orange uppercase tracking-widest whitespace-nowrap hover:underline">
      Ver todo →
    </button>
  </div>
  <div class="grid md:grid-cols-3 gap-4 mb-12">
    <template x-for="post in blogPosts.slice(0,3)" :key="post.id">
      <div @click="page='blog';cargarBlog();abrirPost(post.slug)"
           class="bg-white rounded-[2rem] border border-mt-cream overflow-hidden hover:shadow-lg transition-all cursor-pointer group">
        <img :src="post.imagen_portada||'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400'"
             class="w-full h-40 object-cover group-hover:scale-105 transition-transform">
        <div class="p-4">
          <p class="text-xs text-slate-400 font-bold mb-1"
             x-text="new Date(post.creado_en).toLocaleDateString('es-CL',{day:'numeric',month:'long'})"></p>
          <h4 class="font-black text-mt-brown text-sm italic line-clamp-2" x-text="post.titulo"></h4>
        </div>
      </div>
    </template>
    <div x-show="blogPosts.length===0"
         class="col-span-3 bg-white rounded-[2rem] border border-mt-cream p-8 text-center text-slate-400">
      <i class="fas fa-newspaper text-3xl mb-2 opacity-30"></i>
      <p class="font-bold italic text-sm">Pronto publicaremos artículos y consejos para tu mascota</p>
    </div>
  </div>

  <!-- Mapa y sucursales -->
  <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-mt-cream mb-4">
    <h3 class="text-2xl font-black text-mt-brown mb-6 text-center uppercase italic">Nuestras Tiendas</h3>
    <div class="grid lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 rounded-[2rem] overflow-hidden h-56 shadow-inner">
        <iframe src="https://maps.google.com/maps?q=Balmaceda+4521+La+Serena+Chile&t=&z=16&ie=UTF8&iwloc=&output=embed"
                width="100%" height="100%" style="border:0" allowfullscreen loading="lazy"></iframe>
      </div>
      <div class="space-y-3">
        <div class="p-4 bg-mt-cream rounded-2xl border-l-4 border-mt-orange">
          <p class="font-black text-mt-brown text-xs">📍 Av. Balmaceda 4521 Local #2, La Serena</p>
          <p class="text-xs text-slate-500 mt-1">+569 5379 3135</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-2xl border-l-4 border-mt-brown">
          <p class="font-black text-mt-brown text-xs">📍 Gerónimo Méndez, Coquimbo</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-2xl border-l-4 border-mt-brown">
          <p class="font-black text-mt-brown text-xs">📍 Alessandri 147, El Llano</p>
        </div>
        <a href="https://wa.me/56953793135" target="_blank"
           class="flex items-center justify-center gap-2 p-3 bg-[#25D366] text-white rounded-2xl font-black text-xs hover:bg-green-500 transition-colors">
          <i class="fab fa-whatsapp text-base"></i> Consultar stock ahora
        </a>
      </div>
    </div>
  </div>

</div>
