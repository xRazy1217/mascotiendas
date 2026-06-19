<div x-show="page==='home'" x-cloak class="fade-in">

  <!-- Hero -->
  <div class="relative w-full rounded-[3rem] overflow-hidden shadow-2xl mb-10 border-b-8 border-mt-brown"
       style="background:radial-gradient(circle at top right,var(--color-primary-light),var(--color-primary))">
    <div class="flex flex-col lg:flex-row items-center min-h-[420px] px-8 py-12 md:px-16 text-white text-center lg:text-left">
      <div class="w-full lg:w-1/2 space-y-5">
        <div class="inline-flex items-center gap-2 bg-white/10 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest">
          <i class="fas fa-truck text-mt-orange"></i> Delivery gratis en La Serena y Coquimbo
        </div>
        <h1 class="text-5xl md:text-7xl font-black leading-none uppercase tracking-tighter">
          <span x-text="textos.hero_titulo||'AMOR EN CADA BOCADO.'"></span>
        </h1>
        <p class="text-white/70 text-base font-medium" x-text="textos.hero_subtitulo||'Comida para perros y gatos · Arena sanitaria · Farmacia veterinaria'"></p>
        <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
          <button @click="page='tienda';cargarProductos()"
                  class="bg-mt-brown text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:bg-mt-orange transition-colors">
            Ver Catálogo
          </button>
          <a href="https://wa.me/56953793135" target="_blank"
             class="bg-[#25D366] text-[#0b3d26] px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl hover:bg-[#20ba5a] transition-colors flex items-center gap-2">
            <i class="fab fa-whatsapp text-lg"></i> Pedir por WhatsApp
          </a>
        </div>

      </div>
      <div class="w-full lg:w-1/2 flex justify-center mt-10 lg:mt-0" x-data="{slide:0}" x-init="setInterval(()=>slide=(slide+1)%3, 4000)">
        <div class="relative w-64 md:w-80 h-64 md:h-80 rounded-b-full overflow-hidden shadow-2xl bg-mt-cream">
          <template x-if="textos.hero_imagen && /\.(mp4|webm|ogg|mov)(\?.*)?$/i.test(textos.hero_imagen)">
            <video :src="getProductImage(textos.hero_imagen)" 
                   autoplay loop muted playsinline 
                   class="w-full h-full object-cover"></video>
          </template>
          <template x-if="textos.hero_imagen && !/\.(mp4|webm|ogg|mov)(\?.*)?$/i.test(textos.hero_imagen)">
            <img :src="getProductImage(textos.hero_imagen)" 
                 alt="Mascotiendas" 
                 class="w-full h-full object-cover">
          </template>
          <template x-if="!textos.hero_imagen">
            <div class="w-full h-full">
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
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- Banner delivery -->
  <div class="relative rounded-[2rem] overflow-hidden shadow-xl mb-10 border-b-4 border-mt-brown">
    <!-- Layout: imagen derecha, texto izquierda -->
    <div class="flex flex-col md:flex-row bg-mt-orange">
      <!-- Texto -->
      <div class="flex-1 p-8 md:p-10 flex flex-col justify-center">
        <span class="inline-flex items-center gap-2 bg-mt-cream text-mt-brown px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest mb-4 w-fit">
          <i class="fas fa-truck text-mt-orange"></i> Importante
        </span>
        <h3 class="text-xl md:text-2xl font-black text-white uppercase leading-snug mb-3"
            x-text="textos.delivery_titulo||'Nuestro delivery es exclusivo en la conurbación La Serena – Coquimbo'"></h3>
        <p class="text-slate-200 text-sm font-bold mb-6 leading-relaxed"
           x-text="textos.delivery_texto||'Delivery gratis en todo La Serena y Coquimbo.'"></p>
        <a href="https://wa.me/56953793135?text=Hola!+Quiero+verificar+si+mi+direccion+tiene+cobertura+de+delivery"
           target="_blank"
           class="inline-flex items-center gap-2 bg-[#25D366] text-[#0b3d26] px-6 py-3 rounded-2xl font-black text-sm hover:bg-[#20ba5a] transition-colors shadow-lg w-fit">
          <i class="fab fa-whatsapp text-lg"></i> Verificar cobertura
        </a>
      </div>
      <!-- Imagen -->
      <div class="w-full md:w-72 lg:w-80 flex-shrink-0">
        <img :src="getProductImage(textos.delivery_imagen||'https://mascotiendas.cl/wp-content/uploads/2025/02/IMG-20250130-WA0049-768x768.jpg')" 
             alt="Delivery Gratis" 
             class="w-full h-full object-cover rounded-[2rem]">
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
    <template x-for="cat in categorias.filter(c => c.mostrar_home === 1 || c.mostrar_home === '1')" :key="cat.id">
      <div @click="page=(cat.slug==='farmacia-mascotas'?'farmacia':'tienda'); filtroCategoria=(cat.slug==='farmacia-mascotas'?'':cat.slug); cargarProductos()"
           class="bg-white p-6 rounded-[2rem] border border-mt-cream hover:shadow-lg transition-all cursor-pointer group flex flex-col items-center">
        <div class="w-24 h-24 rounded-full overflow-hidden mb-3 border-2 border-mt-cream group-hover:border-mt-orange transition-colors">
          <img :src="getProductImage(cat.imagen_url || 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400')" :alt="cat.nombre" width="96" height="96" class="w-full h-full object-cover">
        </div>
        <span class="font-black text-mt-brown text-sm uppercase tracking-wider group-hover:text-mt-orange transition-colors text-center" x-text="cat.nombre"></span>
      </div>
    </template>
  </div>

  <!-- Destacados -->
  <div class="flex items-center gap-4 mb-6">
    <h3 class="text-xl font-black text-mt-brown uppercase tracking-tighter whitespace-nowrap">Ofertas de la Semana</h3>
    <div class="h-px flex-grow bg-mt-cream"></div>
    <button @click="page='tienda';cargarProductos()" class="text-xs font-black text-mt-brown hover:text-mt-orange transition-colors uppercase tracking-widest whitespace-nowrap hover:underline">
      Ver todo →
    </button>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 gap-5 mb-12">
    <template x-for="p in destacados" :key="p.id">
      <div @click="abrirProducto(p.id, true, p.slug)"
           class="premium-card bg-white p-5 rounded-[2.5rem] border border-mt-cream cursor-pointer group flex flex-col justify-between shadow-sm relative overflow-hidden">
        <div class="relative mb-4">
          <div class="overflow-hidden rounded-[2rem] aspect-[4/3] w-full bg-mt-cream relative">
            <img :src="getProductImage(p.imagen)" :alt="p.nombre"
                 :style="getImageStyle(p.imagen_crop, 'catalogo')"
                 width="400" height="300"
                 class="premium-card-img w-full h-full object-cover">
          </div>
          <span x-show="!p.en_stock"
                class="absolute top-3 left-3 bg-red-500 text-white text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider z-10 shadow-sm">Sin stock</span>
          <span x-show="p.precio_rebajado"
                class="absolute top-3 right-3 glass-effect text-mt-orange text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider z-10 shadow-sm">Oferta</span>
        </div>
        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5"
              x-text="p.categorias?.split(',')[0]||''"></span>
        <h4 class="font-black text-mt-brown text-sm leading-tight mb-3 line-clamp-2 italic flex-grow hover:text-mt-orange transition-colors duration-200" x-text="p.nombre"></h4>
        <div class="flex items-center justify-between mt-auto">
          <div>
            <template x-if="p.precio_rebajado">
              <span class="text-xs line-through text-slate-400 block font-bold" x-text="formatPrecio(p.precio_normal)"></span>
            </template>
            <p class="font-black text-lg text-mt-brown" x-text="formatPrecio(p.precio_rebajado||p.precio_normal)"></p>
          </div>
          <button @click.stop="addToCart(p)" :disabled="!p.en_stock"
                  :aria-label="'Agregar ' + p.nombre + ' al carrito'"
                  class="premium-btn bg-mt-orange text-white p-3 rounded-xl hover:bg-orange-500 shadow-md active:scale-95 disabled:opacity-40">
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
    <button @click="page='blog';cargarBlog()" class="text-xs font-black text-mt-brown hover:text-mt-orange transition-colors uppercase tracking-widest whitespace-nowrap hover:underline">
      Ver todo →
    </button>
  </div>
  <div class="grid md:grid-cols-3 gap-4 mb-12">
    <template x-for="post in blogPosts.slice(0,3)" :key="post.id">
      <div @click="page='blog';cargarBlog();abrirPost(post.slug)"
           class="bg-white rounded-[2rem] border border-mt-cream overflow-hidden hover:shadow-lg transition-all cursor-pointer group">
        <div class="h-44 overflow-hidden bg-mt-cream">
          <img :src="getProductImage(post.imagen_portada||'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400')" :alt="post.titulo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
        </div>
        <div class="p-4">
          <p class="text-xs text-slate-600 font-bold mb-1"
             x-text="new Date(post.creado_en).toLocaleDateString('es-CL',{day:'numeric',month:'long'})"></p>
          <h4 class="font-black text-mt-brown text-sm italic line-clamp-2" x-text="post.titulo"></h4>
        </div>
      </div>
    </template>
    <div x-show="blogPosts.length===0"
         class="col-span-3 bg-white rounded-[2rem] border border-mt-cream p-8 text-center text-slate-600">
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
                width="100%" height="100%" style="border:0" allowfullscreen loading="lazy" title="Mapa de sucursales Mascotiendas"></iframe>
      </div>
      <div class="space-y-3">
        <div class="p-4 bg-mt-cream rounded-2xl border-l-4 border-mt-orange">
          <p class="font-black text-mt-brown text-xs">📍 Av. Balmaceda 4521 Local #2, La Serena</p>
          <p class="text-xs text-mt-brown mt-1">+569 5379 3135</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-2xl border-l-4 border-mt-brown">
          <p class="font-black text-mt-brown text-xs">📍 Gerónimo Méndez, Coquimbo</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-2xl border-l-4 border-mt-brown">
          <p class="font-black text-mt-brown text-xs">📍 Alessandri 147, El Llano</p>
        </div>
        <a href="https://wa.me/56953793135" target="_blank"
           class="flex items-center justify-center gap-2 p-3 bg-[#25D366] text-[#0b3d26] rounded-2xl font-black text-xs hover:bg-[#20ba5a] transition-colors">
          <i class="fab fa-whatsapp text-base"></i> Consultar stock ahora
        </a>
      </div>
    </div>
  </div>

</div>
