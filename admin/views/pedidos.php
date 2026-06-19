<div x-show="seccion==='pedidos'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Pedidos</h2>
    <span class="text-xs font-bold text-slate-400" x-text="totalPedidos+' pedidos'"></span>
  </div>

  <!-- Filtros -->
  <div class="flex flex-wrap gap-3 mb-4">
    <input x-model="pedidoQ" @input.debounce.400ms="pedidoPagina=1;cargarPedidos()"
           placeholder="Buscar por nombre, email o #ID..."
           class="flex-grow min-w-[200px] px-4 py-2 rounded-xl border border-mt-cream focus:outline-none text-sm font-bold">
    <div class="flex gap-1 flex-wrap">
      <template x-for="e in ['','pendiente','pagado','preparando','enviado','entregado','cancelado']" :key="e">
        <button @click="pedidoFiltro=e;pedidoPagina=1;cargarPedidos()"
                :class="pedidoFiltro===e?'bg-mt-orange text-white':'bg-white text-mt-brown border border-mt-cream'"
                class="px-3 py-1.5 rounded-xl font-bold text-xs capitalize hover:bg-mt-orange hover:text-white transition-colors">
          <span x-text="e||'Todos'"></span>
        </button>
      </template>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">#</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Cliente</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Total</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Estado</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden lg:table-cell">Fecha</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Ver</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="p in listaPedidos" :key="p.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3 font-black text-mt-brown" x-text="'#'+p.id"></td>
            <td class="px-4 py-3 hidden md:table-cell">
              <p class="font-bold text-mt-brown text-sm" x-text="p.nombre_cliente"></p>
              <p class="text-xs text-slate-400" x-text="p.email_cliente"></p>
            </td>
            <td class="px-4 py-3 font-black text-mt-orange" x-text="formatPrecio(p.total)"></td>
            <td class="px-4 py-3">
              <select @change="cambiarEstado(p.id,$event.target.value)" :value="p.estado"
                      class="px-2 py-1 rounded-lg border border-mt-cream text-xs font-bold focus:outline-none bg-white">
                <option value="pendiente">Pendiente</option>
                <option value="pagado">Pagado</option>
                <option value="preparando">Preparando</option>
                <option value="enviado">Enviado</option>
                <option value="entregado">Entregado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </td>
            <td class="px-4 py-3 text-xs text-slate-400 hidden lg:table-cell"
                x-text="new Date(p.creado_en).toLocaleDateString('es-CL')"></td>
            <td class="px-4 py-3">
              <button @click="verPedido(p.id)"
                      class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaPedidos.length===0" class="text-center py-12 text-slate-400 italic">No hay pedidos</div>
  </div>

  <!-- Paginación -->
  <div x-show="pedidoPaginas>1" class="flex justify-center gap-2 mt-4">
    <button @click="pedidoPagina--;cargarPedidos()" :disabled="pedidoPagina<=1"
            class="px-4 py-2 rounded-xl bg-mt-cream font-black text-mt-brown disabled:opacity-40">
      <i class="fas fa-chevron-left"></i>
    </button>
    <span class="px-4 py-2 font-bold text-mt-brown" x-text="pedidoPagina+' / '+pedidoPaginas"></span>
    <button @click="pedidoPagina++;cargarPedidos()" :disabled="pedidoPagina>=pedidoPaginas"
            class="px-4 py-2 rounded-xl bg-mt-cream font-black text-mt-brown disabled:opacity-40">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>

  <!-- Modal detalle pedido -->
  <div x-show="pedidoDetalle" x-cloak @click.self="pedidoDetalle=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2rem] w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl p-6">
      <template x-if="pedidoDetalle">
        <div>
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-black text-mt-brown" x-text="'Pedido #'+pedidoDetalle.id"></h3>
            <button @click="pedidoDetalle=null" class="text-slate-400 hover:text-slate-600">
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>
          <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
            <div class="bg-mt-cream p-3 rounded-xl">
              <p class="font-black text-mt-brown text-xs uppercase mb-1">Cliente</p>
              <p class="font-bold" x-text="pedidoDetalle.nombre_cliente"></p>
              <p class="text-slate-500" x-text="pedidoDetalle.email_cliente"></p>
              <p class="text-slate-500" x-text="pedidoDetalle.telefono||''"></p>
            </div>
            <div class="bg-mt-cream p-3 rounded-xl">
              <p class="font-black text-mt-brown text-xs uppercase mb-1" x-text="pedidoDetalle.metodo_entrega==='retiro'?'Retiro en tienda':'Delivery'"></p>
              <template x-if="pedidoDetalle.metodo_entrega==='retiro'">
                <p class="font-bold" x-text="'Sucursal: '+(pedidoDetalle.sucursal||'-')"></p>
              </template>
              <template x-if="pedidoDetalle.metodo_entrega!=='retiro'">
                <div>
                  <p class="font-bold" x-text="pedidoDetalle.direccion"></p>
                  <p class="text-slate-500" x-text="pedidoDetalle.ciudad"></p>
                </div>
              </template>
              <p class="text-slate-500 mt-1" x-text="'Pago: '+(pedidoDetalle.metodo_pago||'-')"></p>
            </div>
          </div>
          <div class="space-y-2 mb-4">
            <template x-for="item in pedidoDetalle.items" :key="item.id">
              <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                <img :src="getProductImage(item.imagen_url)" class="w-10 h-10 object-cover rounded-lg">
                <p class="flex-grow font-bold text-mt-brown text-sm line-clamp-1" x-text="item.nombre"></p>
                <span class="text-xs text-slate-400 font-bold" x-text="'x'+item.cantidad"></span>
                <span class="font-black text-mt-orange text-sm" x-text="formatPrecio(item.precio*item.cantidad)"></span>
              </div>
            </template>
          </div>
          <div class="flex justify-between font-black text-mt-brown p-4 bg-mt-cream rounded-xl">
            <span>Total</span>
            <span class="text-mt-orange" x-text="formatPrecio(pedidoDetalle.total)"></span>
          </div>
          <p x-show="pedidoDetalle.notas" class="mt-3 text-sm text-slate-500 italic p-3 bg-slate-50 rounded-xl"
             x-text="'Notas: '+pedidoDetalle.notas"></p>
        </div>
      </template>
    </div>
  </div>
</div>
