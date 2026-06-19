<div x-show="seccion==='dashboard'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Dashboard</h2>
    <span class="text-xs text-slate-400 font-bold" x-text="new Date().toLocaleDateString('es-CL',{weekday:'long',year:'numeric',month:'long',day:'numeric'})"></span>
  </div>

  <!-- KPIs -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Ventas Totales</p>
        <i class="fas fa-dollar-sign text-mt-orange opacity-50"></i>
      </div>
      <p class="text-2xl font-black text-mt-brown" x-text="formatPrecio(stats.ventas?.total||0)"></p>
      <p class="text-xs text-slate-400 mt-1" x-text="(stats.ventas?.pedidos||0)+' pedidos'"></p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Ventas Hoy</p>
        <i class="fas fa-calendar-day text-mt-orange opacity-50"></i>
      </div>
      <p class="text-2xl font-black text-mt-brown" x-text="formatPrecio(stats.hoy?.total||0)"></p>
      <p class="text-xs text-slate-400 mt-1" x-text="(stats.hoy?.pedidos||0)+' pedidos hoy'"></p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Clientes</p>
        <i class="fas fa-users text-mt-orange opacity-50"></i>
      </div>
      <p class="text-2xl font-black text-mt-brown" x-text="stats.usuarios||0"></p>
      <p class="text-xs text-slate-400 mt-1">registrados</p>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Pendientes</p>
        <i class="fas fa-clock text-mt-orange opacity-50"></i>
      </div>
      <p class="text-2xl font-black text-mt-orange" x-text="stats.pendientes||0"></p>
      <p class="text-xs text-slate-400 mt-1">pedidos por atender</p>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-4 mb-6">

    <!-- Ventas 7 días -->
    <div class="lg:col-span-2 bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <p class="font-black text-mt-brown mb-4">Ventas últimos 7 días</p>
      <div class="space-y-2">
        <template x-if="stats.ventas7?.length === 0">
          <p class="text-slate-400 italic text-sm text-center py-4">Sin ventas en los últimos 7 días</p>
        </template>
        <template x-for="d in (stats.ventas7||[])" :key="d.fecha">
          <div class="flex items-center gap-3">
            <span class="text-xs font-bold text-slate-400 w-24 flex-shrink-0"
                  x-text="new Date(d.fecha+'T12:00:00').toLocaleDateString('es-CL',{weekday:'short',day:'numeric',month:'short'})"></span>
            <div class="flex-grow bg-mt-cream rounded-full h-2 overflow-hidden">
              <div class="bg-mt-orange h-2 rounded-full transition-all"
                   :style="'width:'+Math.min(100, Math.round((d.total/(stats.ventas?.total||1))*100*7))+'%'"></div>
            </div>
            <span class="text-xs font-black text-mt-brown w-24 text-right flex-shrink-0" x-text="formatPrecio(d.total)"></span>
          </div>
        </template>
      </div>
    </div>

    <!-- Alertas de stock -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <div class="flex items-center justify-between mb-4">
        <p class="font-black text-mt-brown">Alertas de Stock</p>
        <div class="flex gap-2">
          <span class="px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-xs font-black" x-text="(stats.sin_stock||0)+' sin stock'"></span>
          <span class="px-2 py-0.5 bg-yellow-100 text-yellow-600 rounded-full text-xs font-black" x-text="(stats.bajo_stock||0)+' bajo'"></span>
        </div>
      </div>
      <div class="space-y-2 max-h-48 overflow-y-auto">
        <template x-for="p in bajoStockList" :key="p.id">
          <div class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-50 cursor-pointer"
               @click="seccion='productos'">
            <img :src="getProductImage(p.imagen)" class="w-8 h-8 object-cover rounded-lg flex-shrink-0">
            <div class="flex-grow min-w-0">
              <p class="text-xs font-bold text-mt-brown line-clamp-1" x-text="p.nombre"></p>
              <p class="text-[10px] text-slate-400" x-text="p.en_stock ? 'Stock: '+p.inventario_actual : 'Sin stock'"></p>
            </div>
            <span :class="p.en_stock ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'"
                  class="text-[10px] font-black px-1.5 py-0.5 rounded-full flex-shrink-0"
                  x-text="p.en_stock ? 'Bajo' : 'Agotado'"></span>
          </div>
        </template>
        <template x-if="bajoStockList.length===0">
          <p class="text-slate-400 italic text-sm text-center py-4">Todo en orden</p>
        </template>
      </div>
    </div>
  </div>

  <div class="grid lg:grid-cols-2 gap-4">

    <!-- Top productos -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <p class="font-black text-mt-brown mb-4">Top Productos Vendidos</p>
      <div class="space-y-3">
        <template x-for="(p,i) in (stats.top||[])" :key="p.nombre">
          <div class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-mt-cream text-mt-brown font-black text-xs flex items-center justify-center flex-shrink-0"
                  x-text="i+1"></span>
            <p class="flex-grow text-sm font-bold text-mt-brown line-clamp-1" x-text="p.nombre"></p>
            <span class="text-xs font-black text-slate-400" x-text="p.vendidos+' uds'"></span>
            <span class="text-xs font-black text-mt-orange" x-text="formatPrecio(p.ingresos)"></span>
          </div>
        </template>
        <template x-if="!stats.top?.length">
          <p class="text-slate-400 italic text-sm text-center py-4">Sin ventas aún</p>
        </template>
      </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
      <p class="font-black text-mt-brown mb-4">Accesos Rápidos</p>
      <div class="grid grid-cols-2 gap-3">
        <button @click="seccion='productos';cargarProductos()"
                class="flex flex-col items-center gap-2 p-4 bg-mt-cream rounded-2xl hover:bg-mt-orange hover:text-white transition-colors group">
          <i class="fas fa-box text-mt-orange group-hover:text-white text-xl"></i>
          <span class="text-xs font-black text-mt-brown group-hover:text-white uppercase">Productos</span>
        </button>
        <button @click="seccion='pedidos';cargarPedidos()"
                class="flex flex-col items-center gap-2 p-4 bg-mt-cream rounded-2xl hover:bg-mt-orange hover:text-white transition-colors group">
          <i class="fas fa-shopping-bag text-mt-orange group-hover:text-white text-xl"></i>
          <span class="text-xs font-black text-mt-brown group-hover:text-white uppercase">Pedidos</span>
          <span x-show="stats.pendientes>0" x-text="stats.pendientes+' pendientes'"
                class="text-[10px] bg-red-500 text-white px-2 py-0.5 rounded-full font-black"></span>
        </button>
        <button @click="seccion='cupones';cargarCupones()"
                class="flex flex-col items-center gap-2 p-4 bg-mt-cream rounded-2xl hover:bg-mt-orange hover:text-white transition-colors group">
          <i class="fas fa-tag text-mt-orange group-hover:text-white text-xl"></i>
          <span class="text-xs font-black text-mt-brown group-hover:text-white uppercase">Cupones</span>
        </button>
        <button @click="seccion='marketing';cargarNewsletter();cargarCarritos();cargarBlacklist()"
                class="flex flex-col items-center gap-2 p-4 bg-mt-cream rounded-2xl hover:bg-mt-orange hover:text-white transition-colors group">
          <i class="fas fa-envelope text-mt-orange group-hover:text-white text-xl"></i>
          <span class="text-xs font-black text-mt-brown group-hover:text-white uppercase">Marketing</span>
        </button>
      </div>

      <!-- Toggle descuento primer pedido -->
      <div class="mt-4 p-4 bg-mt-cream rounded-2xl space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-black text-mt-brown text-sm">Descuento primer pedido</p>
            <p class="text-xs text-slate-400">Automático para clientes nuevos</p>
          </div>
          <button @click="toggleDescuentoPrimerPedido()"
                  :class="config.descuento_primer_pedido==='1' ? 'bg-mt-orange' : 'bg-slate-300'"
                  class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none">
            <span :class="config.descuento_primer_pedido==='1' ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-200"></span>
          </button>
        </div>
        <div x-show="config.descuento_primer_pedido==='1'" class="flex items-center gap-2 pt-1">
          <span class="text-xs font-bold text-mt-brown">Porcentaje de descuento:</span>
          <div class="relative w-24">
            <input type="number" min="0" max="100" x-model="config.descuento_primer_pedido_valor"
                   @change="guardarConfig('descuento_primer_pedido_valor', config.descuento_primer_pedido_valor)"
                   placeholder="10"
                   class="w-full px-3 py-1.5 pr-8 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">%</span>
          </div>
        </div>
      </div>

      <!-- Toggle y config popup -->
      <div class="mt-3 p-4 bg-mt-cream rounded-2xl space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-black text-mt-brown text-sm">Popup de descuento</p>
            <p class="text-xs text-slate-400">Modal que aparece al salir del sitio</p>
          </div>
          <button @click="toggleConfig('popup_activo')"
                  :class="config.popup_activo==='1' ? 'bg-mt-orange' : 'bg-slate-300'"
                  class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none">
            <span :class="config.popup_activo==='1' ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-200"></span>
          </button>
        </div>
        <div x-show="config.popup_activo==='1'" class="space-y-2">
          <input x-model="config.popup_titulo" @change="guardarConfig('popup_titulo', config.popup_titulo)"
                 placeholder="Título del popup"
                 class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white">
          <textarea x-model="config.popup_texto" @change="guardarConfig('popup_texto', config.popup_texto)"
                    placeholder="Texto del popup" rows="2"
                    class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white resize-none"></textarea>
          <input x-model="config.popup_descuento_valor" @change="guardarConfig('popup_descuento_valor', config.popup_descuento_valor)"
                 placeholder="Texto de descuento (ej: 10% de descuento)"
                 class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white">
        </div>
      </div>

      <!-- Diseño & Colores -->
      <div class="mt-3 p-4 bg-mt-cream rounded-2xl space-y-3">
        <div class="flex items-center gap-2 mb-1">
          <i class="fas fa-palette text-mt-orange"></i>
          <p class="font-black text-mt-brown text-sm">Diseño & Colores</p>
        </div>
        <p class="text-xs text-slate-400 -mt-2">Personaliza la paleta de colores de la tienda</p>

        <div class="space-y-3 pt-1">
          <!-- Color Principal -->
          <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-bold text-mt-brown">Color Principal (Marrón/Base):</span>
            <div class="flex items-center gap-2">
              <input type="color" x-model="config.theme_color_primary"
                     @change="guardarConfig('theme_color_primary', config.theme_color_primary)"
                     class="w-8 h-8 rounded-lg cursor-pointer border border-white focus:outline-none">
              <span class="text-xs font-mono font-bold text-slate-500 uppercase" x-text="config.theme_color_primary"></span>
            </div>
          </div>

          <!-- Color Secundario -->
          <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-bold text-mt-brown">Color Secundario (Naranjo/Highlight):</span>
            <div class="flex items-center gap-2">
              <input type="color" x-model="config.theme_color_secondary"
                     @change="guardarConfig('theme_color_secondary', config.theme_color_secondary)"
                     class="w-8 h-8 rounded-lg cursor-pointer border border-white focus:outline-none">
              <span class="text-xs font-mono font-bold text-slate-500 uppercase" x-text="config.theme_color_secondary"></span>
            </div>
          </div>

          <!-- Color Crema / Fondos -->
          <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-bold text-mt-brown">Color Crema (Fondos/Bordes):</span>
            <div class="flex items-center gap-2">
              <input type="color" x-model="config.theme_color_cream"
                     @change="guardarConfig('theme_color_cream', config.theme_color_cream)"
                     class="w-8 h-8 rounded-lg cursor-pointer border border-white focus:outline-none">
              <span class="text-xs font-mono font-bold text-slate-500 uppercase" x-text="config.theme_color_cream"></span>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="pt-2 border-t border-white/60 grid grid-cols-2 gap-2">
            <button @click="invertirColores()"
                    class="px-2 py-1.5 bg-mt-orange hover:bg-orange-500 text-white rounded-xl font-bold text-xs transition-colors flex items-center justify-center gap-1">
              <i class="fas fa-arrows-alt-h"></i>
              <span>Invertir Colores</span>
            </button>
            <button @click="restablecerColores()"
                    class="px-2 py-1.5 bg-slate-200 hover:bg-slate-300 text-mt-brown rounded-xl font-bold text-xs transition-colors flex items-center justify-center gap-1">
              <i class="fas fa-undo"></i>
              <span>Restablecer</span>
            </button>
          </div>
        </div>
      </div>

      <!-- SEO & Tracking -->
      <div class="mt-3 p-4 bg-mt-cream rounded-2xl space-y-3">
        <div class="flex items-center gap-2 mb-1">
          <i class="fas fa-chart-line text-mt-orange"></i>
          <p class="font-black text-mt-brown text-sm">SEO & Google Analytics</p>
        </div>
        <p class="text-xs text-slate-400 -mt-2">Configura el tracking de analytics y conversiones</p>

        <div class="space-y-2">
          <div>
            <label class="text-xs font-bold text-mt-brown">Google Tag Manager ID</label>
            <input x-model="config.gtm_id" @change="guardarConfig('gtm_id', config.gtm_id)"
                   placeholder="GTM-XXXXXXX"
                   class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white font-mono">
          </div>
          <div>
            <label class="text-xs font-bold text-mt-brown">Google Analytics 4 ID <span class="text-slate-400 font-normal">(fallback sin GTM)</span></label>
            <input x-model="config.ga4_id" @change="guardarConfig('ga4_id', config.ga4_id)"
                   placeholder="G-XXXXXXXXXX"
                   class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white font-mono">
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-xs font-bold text-mt-brown">Google Ads Conversion ID</label>
              <input x-model="config.gads_conversion_id" @change="guardarConfig('gads_conversion_id', config.gads_conversion_id)"
                     placeholder="AW-XXXXXXXXX"
                     class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white font-mono">
            </div>
            <div>
              <label class="text-xs font-bold text-mt-brown">Conversion Label</label>
              <input x-model="config.gads_conversion_label" @change="guardarConfig('gads_conversion_label', config.gads_conversion_label)"
                     placeholder="XXXXXXXXXXXX"
                     class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white font-mono">
            </div>
          </div>
        </div>

        <!-- Links útiles SEO -->
        <div class="pt-2 border-t border-white/60 space-y-1">
          <p class="text-xs font-black text-mt-brown uppercase tracking-wider">Herramientas SEO</p>
          <div class="flex flex-wrap gap-2">
            <a href="/sitemap.xml" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-mt-orange hover:underline">
              <i class="fas fa-sitemap"></i> Sitemap XML
            </a>
            <a href="/feed/google-shopping.xml" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-mt-orange hover:underline">
              <i class="fas fa-shopping-cart"></i> Feed Google Shopping
            </a>
            <a href="/robots.txt" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-mt-orange hover:underline">
              <i class="fas fa-robot"></i> robots.txt
            </a>
          </div>
        </div>
      </div>

      <!-- WhatsApp & Chatbot -->
      <div class="mt-3 p-4 bg-mt-cream rounded-2xl space-y-3">
        <div class="flex items-center gap-2 mb-1">
          <i class="fab fa-whatsapp text-mt-orange"></i>
          <p class="font-black text-mt-brown text-sm">WhatsApp & Chatbot</p>
        </div>
        <p class="text-xs text-slate-400 -mt-2">Configura los números de WhatsApp para alertas y la tienda</p>

        <div class="space-y-2">
          <div>
            <label class="text-xs font-bold text-mt-brown">Número del Administrador (Alertas de Pedido)</label>
            <input type="text" x-model="config.admin_whatsapp_number"
                   @change="guardarConfig('admin_whatsapp_number', config.admin_whatsapp_number)"
                   placeholder="56920571475"
                   class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white font-mono">
            <span class="text-[10px] text-slate-400 block mt-0.5 leading-tight">Recibe notificaciones de pedidos nuevos, modificaciones y anulaciones (ej: 569XXXXXXXX).</span>
          </div>
          <div>
            <label class="text-xs font-bold text-mt-brown">Número del Bot (Público de la Tienda)</label>
            <input type="text" x-model="config.bot_whatsapp_number"
                   @change="guardarConfig('bot_whatsapp_number', config.bot_whatsapp_number)"
                   placeholder="56953793135"
                   class="w-full px-3 py-2 rounded-xl border border-white focus:outline-none focus:border-mt-orange font-bold text-sm bg-white font-mono">
            <span class="text-[10px] text-slate-400 block mt-0.5 leading-tight">Número de contacto para el chat flotante y redirección del checkout (ej: 569XXXXXXXX).</span>
          </div>
          <div class="pt-3 border-t border-white/60 flex items-center justify-between">
            <div>
              <p class="font-black text-mt-brown text-sm">Responder solo a chats nuevos</p>
              <p class="text-xs text-slate-400 leading-tight">Ignora contactos guardados en la agenda de tu teléfono.</p>
            </div>
            <button @click="toggleConfig('bot_only_respond_to_unknown')"
                    :class="config.bot_only_respond_to_unknown==='1' ? 'bg-mt-orange' : 'bg-slate-300'"
                    class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none flex-shrink-0">
              <span :class="config.bot_only_respond_to_unknown==='1' ? 'translate-x-6' : 'translate-x-1'"
                    class="inline-block w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-200"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
