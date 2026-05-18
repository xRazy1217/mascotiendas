<footer class="bg-mt-brown text-white pt-20 mt-20 border-t-8 border-mt-orange relative">
  <div class="container mx-auto px-4 -mb-12 relative z-10">
    <div class="bg-mt-orange p-8 rounded-[2.5rem] shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="text-center md:text-left">
        <h3 class="text-2xl font-black text-mt-brown uppercase mb-1">🐾 ¡Súmate a la manada!</h3>
        <p class="font-bold text-mt-brown/80">Recibe beneficios exclusivos.</p>
      </div>
      <form @submit.prevent="suscribir()" class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
        <input x-model="newsletterEmail" type="email" required placeholder="Tu correo"
               class="px-6 py-3 rounded-2xl text-mt-brown font-bold focus:outline-none w-full md:w-64">
        <button type="submit" class="bg-mt-brown text-white px-8 py-3 rounded-2xl font-black hover:bg-white hover:text-mt-brown transition-all uppercase tracking-widest text-xs">
          Suscribirme
        </button>
      </form>
    </div>
  </div>

  <div class="bg-mt-brown pt-24 pb-10">
    <div class="container mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-10 text-center md:text-left">

      <div class="col-span-2 md:col-span-1">
        <img src="https://mascotiendas.cl/wp-content/uploads/2023/07/cropped-mascotiendas-cl-comida-para-perros-gatos-farmacia-peluqueria-tienda-mascotas-veterinaria-la-serena-coquimbo-scaled-1-1024x542.webp"
             alt="Mascotiendas" class="h-12 w-auto object-contain mb-3">
        <p class="text-white/50 text-xs leading-relaxed italic mb-3">Líderes en nutrición regional. La Serena y Coquimbo desde 2015.</p>
        <p class="text-xs font-bold text-white/70" x-text="textos.footer_email||'ventas@mascotiendas.cl'"></p>
        <p class="text-xs font-bold text-white/70 mt-1" x-text="textos.footer_telefono||'+569 5379 3135'"></p>
        <p class="text-xs font-bold text-white/70 mt-1" x-text="textos.footer_direccion||'Av. Balmaceda 4521 Local #2, La Serena'"></p>
      </div>

      <div>
        <h4 class="font-black text-mt-orange uppercase tracking-widest text-xs mb-4">Tienda</h4>
        <ul class="space-y-2 text-xs font-bold text-white/70">
          <li><button @click="page='tienda';cargarProductos()" class="hover:text-mt-orange transition-colors">Catálogo</button></li>
          <li><button @click="filtroCategoria='comida-perros';page='tienda';cargarProductos()" class="hover:text-mt-orange transition-colors">Comida Perros</button></li>
          <li><button @click="filtroCategoria='comida-gatos';page='tienda';cargarProductos()" class="hover:text-mt-orange transition-colors">Comida Gatos</button></li>
          <li><button @click="filtroCategoria='arena-gatos';page='tienda';cargarProductos()" class="hover:text-mt-orange transition-colors">Arena Sanitaria</button></li>
          <li><button @click="page='farmacia';cargarProductos()" class="hover:text-mt-orange transition-colors">Farmacia Veterinaria</button></li>
          <li><button @click="page='blog';cargarBlog()" class="hover:text-mt-orange transition-colors">Blog</button></li>
        </ul>
      </div>

      <div>
        <h4 class="font-black text-mt-orange uppercase tracking-widest text-xs mb-4">Mi Cuenta</h4>
        <ul class="space-y-2 text-xs font-bold text-white/70">
          <li><button @click="page='login'" class="hover:text-mt-orange transition-colors">Iniciar Sesión</button></li>
          <li><button @click="page='registro'" class="hover:text-mt-orange transition-colors">Registrarse</button></li>
          <li><button @click="page='perfil'" class="hover:text-mt-orange transition-colors">Mis Pedidos</button></li>
          <li><button @click="page='favoritos'" class="hover:text-mt-orange transition-colors">Favoritos</button></li>
          <li><button @click="page='carrito'" class="hover:text-mt-orange transition-colors">Carrito</button></li>
        </ul>
      </div>

      <div>
        <h4 class="font-black text-mt-orange uppercase tracking-widest text-xs mb-4">Legal</h4>
        <ul class="space-y-2 text-xs font-bold text-white/70">
          <li><button @click="abrirPagina('politica-devoluciones')" class="hover:text-mt-orange transition-colors">Política de Devoluciones</button></li>
          <li><button @click="abrirPagina('politica-privacidad')" class="hover:text-mt-orange transition-colors">Política de Privacidad</button></li>
          <li><a href="/sitemap.xml" target="_blank" class="hover:text-mt-orange transition-colors">Sitemap XML</a></li>
        </ul>
        <h4 class="font-black text-mt-orange uppercase tracking-widest text-xs mb-4 mt-6">Pagos</h4>
        <div class="flex justify-center md:justify-start gap-4 text-white/60">
          <div class="flex items-center gap-1 text-xs font-bold">
            <i class="fas fa-money-bill-wave text-mt-orange text-lg"></i>
            <span>Efectivo</span>
          </div>
          <div class="flex items-center gap-1 text-xs font-bold">
            <i class="fas fa-university text-mt-orange text-lg"></i>
            <span>Transferencia</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom bar -->
    <div class="container mx-auto px-4 mt-10 pt-6 border-t border-white/5 flex flex-col md:flex-row items-center justify-between gap-3">
      <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest">
        © 2026 Mascotiendas.cl — La Serena & Coquimbo
      </p>
      <div class="flex items-center gap-4">
        <a href="https://wa.me/56953793135" target="_blank" class="text-white/30 hover:text-[#25D366] transition-colors text-lg">
          <i class="fab fa-whatsapp"></i>
        </a>
        <a :href="textos.footer_instagram||'https://instagram.com'" target="_blank" class="text-white/30 hover:text-pink-400 transition-colors text-lg">
          <i class="fab fa-instagram"></i>
        </a>
        <a :href="textos.footer_facebook||'https://facebook.com'" target="_blank" class="text-white/30 hover:text-blue-400 transition-colors text-lg">
          <i class="fab fa-facebook"></i>
        </a>
      </div>
    </div>
  </div>
</footer>
