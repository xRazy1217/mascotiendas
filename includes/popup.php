<!-- POPUP: Campaña activa o Newsletter -->
<div x-show="popupVisible" x-cloak
     @click.self="popupVisible=false;popupCerrado=true;if(campanaActiva)localStorage.setItem('campana_cerrada_'+campanaActiva.id,'1')"
     class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
  <div x-show="popupVisible"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100"
       class="bg-white rounded-[2.5rem] max-w-md w-full shadow-2xl overflow-hidden">

    <!-- Campaña activa -->
    <template x-if="campanaActiva">
      <div>
        <div class="relative">
          <img x-show="campanaActiva.imagen_url" :src="campanaActiva.imagen_url"
               class="w-full h-48 object-cover">
          <button @click="popupVisible=false;localStorage.setItem('campana_cerrada_'+campanaActiva.id,'1')"
                  class="absolute top-3 right-3 bg-black/40 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/60">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="p-6 text-center">
          <h3 class="text-2xl font-black text-mt-brown uppercase mb-2" x-text="campanaActiva.titulo"></h3>
          <p class="text-slate-500 text-sm mb-4" x-text="campanaActiva.texto"></p>
          <template x-if="campanaActiva.btn_texto && campanaActiva.btn_url">
            <a :href="campanaActiva.btn_url"
               class="inline-block bg-mt-orange text-white px-8 py-3 rounded-2xl font-black uppercase tracking-widest hover:bg-orange-500 transition-colors"
               x-text="campanaActiva.btn_texto"></a>
          </template>
          <button @click="popupVisible=false;localStorage.setItem('campana_cerrada_'+campanaActiva.id,'1')"
                  class="block mx-auto mt-3 text-xs text-slate-400 hover:text-slate-600">
            Cerrar
          </button>
        </div>
      </div>
    </template>

    <!-- Popup newsletter (sin campaña activa) -->
    <template x-if="!campanaActiva">
      <div>
        <div class="bg-mt-brown p-6 text-center relative">
          <button @click="popupVisible=false;popupCerrado=true"
                  class="absolute top-4 right-4 text-white/50 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
          </button>
          <div class="text-5xl mb-2">🐾</div>
          <h3 class="text-2xl font-black text-white uppercase italic" x-text="config.popup_titulo||'¡Espera!'"></h3>
          <p class="text-mt-orange font-black text-lg">10% de descuento</p>
          <p class="text-white/70 text-sm mt-1">en tu primera compra</p>
        </div>
        <div class="p-6 text-center">
          <p class="text-mt-brown font-bold text-sm mb-4" x-text="config.popup_texto||'Suscríbete y recibe tu cupón al instante'"></p>
          <form @submit.prevent="suscribirPopup()" class="space-y-3">
            <input x-model="popupEmail" type="email" required placeholder="Tu correo electrónico"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm text-center">
            <button type="submit"
                    class="w-full bg-mt-orange text-white py-3 rounded-2xl font-black uppercase tracking-widest hover:bg-orange-500 transition-colors">
              Quiero mi descuento
            </button>
          </form>
          <button @click="popupVisible=false;popupCerrado=true"
                  class="mt-3 text-xs text-slate-400 hover:text-slate-600 transition-colors">
            No gracias, prefiero pagar precio completo
          </button>
        </div>
      </div>
    </template>

  </div>
</div>
