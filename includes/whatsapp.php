<!-- WHATSAPP FLOTANTE -->
<a :href="'https://wa.me/56953793135?text=Hola!+Consulta+desde+sucursal+'+encodeURIComponent(sucursalNombre())"
   target="_blank"
   class="fixed bottom-6 right-6 bg-[#25D366] text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 hover:scale-105 transition-all z-40">
  <i class="fab fa-whatsapp text-2xl"></i>
  <div class="leading-none text-left font-black uppercase text-[8px]">
    <span class="opacity-70 mb-1 block">Consulta Stock:</span>
    <span class="text-xs" x-text="sucursalNombre()"></span>
  </div>
</a>
