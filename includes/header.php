<header class="sticky top-0 z-50 bg-white shadow-sm border-b border-mt-cream">
  <nav class="container mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">

    <div class="flex items-center gap-2 cursor-pointer" @click="page='home'">
      <img src="https://mascotiendas.cl/wp-content/uploads/2023/07/cropped-mascotiendas-cl-comida-para-perros-gatos-farmacia-peluqueria-tienda-mascotas-veterinaria-la-serena-coquimbo-scaled-1-1024x542.webp"
           alt="Mascotiendas" class="h-14 w-auto object-contain">
    </div>

    <div class="flex items-center gap-4">
      <button @click="page='home'" class="text-[10px] font-black text-mt-brown uppercase tracking-widest hover:text-mt-orange transition-colors hidden md:block">Inicio</button>
      <button @click="page='tienda';cargarProductos()" class="text-[10px] font-black text-mt-brown uppercase tracking-widest hover:text-mt-orange transition-colors hidden md:block">Tienda</button>
      <button @click="page='farmacia';cargarProductos()" class="text-[10px] font-black text-mt-brown uppercase tracking-widest hover:text-mt-orange transition-colors hidden md:block">Farmacia</button>
      <button @click="page='comunidad'" class="text-[10px] font-black text-mt-brown uppercase tracking-widest hover:text-mt-orange transition-colors hidden md:block">Comunidad</button>
      <button @click="page='blog';cargarBlog()" class="text-[10px] font-black text-mt-brown uppercase tracking-widest hover:text-mt-orange transition-colors hidden md:block">Blog</button>

      <div class="relative" x-data="{open:false}">
        <button @click="open=!open" aria-label="Menú de usuario" class="text-mt-brown hover:text-mt-orange transition-colors">
          <i class="fas fa-user text-lg"></i>
        </button>
        <div x-show="open" x-cloak @click.outside="open=false"
             class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-mt-cream p-2 z-50">
          <template x-if="!usuario">
            <div>
              <button @click="open=false;page='login'" class="w-full text-left px-4 py-2 text-sm font-bold text-mt-brown hover:bg-mt-cream rounded-xl">Iniciar sesión</button>
              <button @click="open=false;page='registro'" class="w-full text-left px-4 py-2 text-sm font-bold text-mt-brown hover:bg-mt-cream rounded-xl">Registrarse</button>
            </div>
          </template>
          <template x-if="usuario">
            <div>
              <p class="px-4 py-2 text-xs text-slate-400 font-bold truncate" x-text="'Hola, '+usuario.nombre"></p>
              <button @click="open=false;page='perfil'" class="w-full text-left px-4 py-2 text-sm font-bold text-mt-brown hover:bg-mt-cream rounded-xl">Mi cuenta</button>
              <template x-if="usuario.rol==='admin'">
                <a href="<?= $base_path ?>/admin/" class="block px-4 py-2 text-sm font-bold text-mt-orange hover:bg-mt-cream rounded-xl">Panel Admin</a>
              </template>
              <button @click="logout();open=false" class="w-full text-left px-4 py-2 text-sm font-bold text-red-500 hover:bg-red-50 rounded-xl">Cerrar sesión</button>
            </div>
          </template>
        </div>
      </div>

      <button @click="page='favoritos'" aria-label="Ver favoritos" class="text-mt-brown hover:text-mt-orange transition-colors hidden md:block">
        <i class="fas fa-heart text-lg"></i>
      </button>

      <button @click="page='carrito'" aria-label="Ver carrito" class="relative text-mt-brown hover:text-mt-orange transition-colors">
        <i class="fas fa-shopping-basket text-xl"></i>
        <span x-show="cartCount>0" x-text="cartCount"
              class="absolute -top-2 -right-2 bg-mt-orange text-white text-[10px] font-black rounded-full h-4 w-4 flex items-center justify-center"></span>
      </button>
    </div>

  </nav>
</header>
