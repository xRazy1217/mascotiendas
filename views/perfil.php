<div x-show="page==='perfil'" x-cloak class="fade-in max-w-3xl mx-auto space-y-6">

  <!-- Tabs -->
  <div class="flex gap-2 bg-white p-2 rounded-2xl border border-mt-cream shadow-sm">
    <button @click="perfilTab='datos'"
            :class="perfilTab==='datos'?'bg-mt-orange text-white':'text-mt-brown hover:bg-mt-cream'"
            class="flex-1 py-2.5 rounded-xl font-black text-sm uppercase tracking-widest transition-colors">
      <i class="fas fa-user mr-2"></i>Mis Datos
    </button>
    <button @click="perfilTab='direcciones'"
            :class="perfilTab==='direcciones'?'bg-mt-orange text-white':'text-mt-brown hover:bg-mt-cream'"
            class="flex-1 py-2.5 rounded-xl font-black text-sm uppercase tracking-widest transition-colors">
      <i class="fas fa-map-marker-alt mr-2"></i>Direcciones
    </button>
    <button @click="perfilTab='pedidos'"
            :class="perfilTab==='pedidos'?'bg-mt-orange text-white':'text-mt-brown hover:bg-mt-cream'"
            class="flex-1 py-2.5 rounded-xl font-black text-sm uppercase tracking-widest transition-colors">
      <i class="fas fa-shopping-bag mr-2"></i>Pedidos
    </button>
  </div>

  <!-- TAB: DATOS PERSONALES -->
  <div x-show="perfilTab==='datos'" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-mt-cream">
    <h3 class="text-lg font-black text-mt-brown uppercase mb-6">Datos Personales</h3>
    <template x-if="perfil">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Nombre</label>
            <input x-model="perfil.usuario.nombre"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>
          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Apellido</label>
            <input x-model="perfil.usuario.apellido"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>
        </div>
        <div>
          <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Email</label>
          <input disabled :value="perfil.usuario.email"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream bg-slate-50 font-bold text-sm text-slate-400">
        </div>
        <div>
          <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Teléfono</label>
          <input x-model="perfil.usuario.telefono" placeholder="+56 9 XXXX XXXX"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        </div>
        <div>
          <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Nueva Contraseña</label>
          <input type="password" x-model="nuevaPassword" placeholder="Dejar vacío para no cambiar"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        </div>
        <button @click="guardarPerfil()"
                class="w-full bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
          <i class="fas fa-save mr-2"></i>Guardar Cambios
        </button>
      </div>
    </template>
    <template x-if="!perfil">
      <div class="text-center py-8"><i class="fas fa-spinner fa-spin text-mt-orange text-3xl"></i></div>
    </template>
  </div>

  <!-- TAB: DIRECCIONES -->
  <div x-show="perfilTab==='direcciones'" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-mt-cream">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-black text-mt-brown uppercase">Mis Direcciones</h3>
      <button @click="abrirFormDireccion(null)"
              class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
        <i class="fas fa-plus mr-1"></i> Nueva
      </button>
    </div>

    <div class="space-y-3">
      <template x-for="dir in (perfil?.direcciones||[])" :key="dir.id">
        <div :class="dir.predeterminada?'border-mt-orange bg-mt-cream':'border-mt-cream bg-slate-50'"
             class="p-4 rounded-2xl border-2 transition-all">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-grow">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-black text-mt-brown text-sm" x-text="dir.alias||'Dirección'"></span>
                <span x-show="dir.predeterminada"
                      class="text-[9px] font-black bg-mt-orange text-white px-2 py-0.5 rounded-full uppercase">
                  Predeterminada
                </span>
              </div>
              <p class="text-sm text-slate-600 font-bold" x-text="dir.calle+' '+dir.numero+(dir.depto?', '+dir.depto:'')"></p>
              <p class="text-xs text-slate-400" x-text="dir.ciudad+', '+dir.region"></p>
            </div>
            <div class="flex gap-2 flex-shrink-0">
              <button x-show="!dir.predeterminada" @click="setPredeterminada(dir.id)"
                      class="px-3 py-1.5 bg-white text-mt-brown rounded-lg font-bold text-xs border border-mt-cream hover:bg-mt-orange hover:text-white hover:border-mt-orange transition-colors">
                <i class="fas fa-star"></i>
              </button>
              <button @click="abrirFormDireccion(dir)"
                      class="px-3 py-1.5 bg-white text-mt-brown rounded-lg font-bold text-xs border border-mt-cream hover:bg-mt-orange hover:text-white hover:border-mt-orange transition-colors">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="eliminarDireccion(dir.id)"
                      class="px-3 py-1.5 bg-white text-red-400 rounded-lg font-bold text-xs border border-red-100 hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </template>

      <div x-show="!perfil?.direcciones?.length" class="text-center py-10 text-slate-400">
        <i class="fas fa-map-marker-alt text-4xl mb-3 opacity-30"></i>
        <p class="font-bold italic text-sm">No tienes direcciones guardadas</p>
        <button @click="abrirFormDireccion(null)" class="mt-3 text-mt-orange font-black text-sm hover:underline">
          Agregar dirección →
        </button>
      </div>
    </div>

    <!-- Modal dirección -->
    <div x-show="formDireccion" x-cloak @click.self="formDireccion=null"
         class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
      <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-black text-mt-brown"
              x-text="formDireccion?.id?'Editar Dirección':'Nueva Dirección'"></h3>
          <button @click="formDireccion=null" class="text-slate-400 hover:text-slate-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        <template x-if="formDireccion">
          <div class="space-y-3">
            <input x-model="formDireccion.alias" placeholder="Alias (ej: Casa, Trabajo)"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            <div class="grid grid-cols-3 gap-3">
              <div class="col-span-2">
                <input x-model="formDireccion.calle" required placeholder="Calle / Avenida *"
                       class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
              </div>
              <input x-model="formDireccion.numero" required placeholder="Número *"
                     class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            </div>
            <input x-model="formDireccion.depto" placeholder="Depto / Casa / Block (opcional)"
                   class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            <div class="grid grid-cols-2 gap-3">
              <input x-model="formDireccion.ciudad" required placeholder="Ciudad *"
                     class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
              <input x-model="formDireccion.region" placeholder="Región"
                     class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
            </div>
            <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
              <input type="checkbox" x-model="formDireccion.predeterminada" class="w-4 h-4 accent-mt-orange">
              Establecer como predeterminada
            </label>
            <div class="flex gap-3 pt-2">
              <button @click="guardarDireccion()"
                      class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
                Guardar
              </button>
              <button @click="formDireccion=null"
                      class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
                Cancelar
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- TAB: PEDIDOS -->
  <div x-show="perfilTab==='pedidos'" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-mt-cream">
    <h3 class="text-lg font-black text-mt-brown uppercase mb-6">Mis Pedidos</h3>
    <template x-if="perfil && perfil.pedidos.length===0">
      <div class="text-center py-10 text-slate-400">
        <i class="fas fa-box-open text-4xl mb-3 opacity-30"></i>
        <p class="font-bold italic">Aún no tienes pedidos</p>
        <button @click="page='tienda';cargarProductos()" class="mt-3 text-mt-orange font-black text-sm hover:underline">
          Ir a la tienda →
        </button>
      </div>
    </template>
    <div class="space-y-3">
      <template x-for="p in (perfil?.pedidos||[])" :key="p.id">
        <div class="p-4 bg-slate-50 rounded-2xl border border-mt-cream hover:border-mt-orange transition-colors cursor-pointer"
             @click="verDetallePedido(p.id)">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-black text-mt-brown text-sm">Pedido #<span x-text="p.id"></span></p>
              <p class="text-xs text-slate-400 mt-0.5"
                 x-text="new Date(p.creado_en).toLocaleDateString('es-CL',{year:'numeric',month:'long',day:'numeric'})"></p>
            </div>
            <div class="flex items-center gap-3">
              <span class="px-3 py-1 rounded-full text-xs font-black uppercase"
                    :class="{
                      'bg-yellow-100 text-yellow-700': p.estado==='pendiente',
                      'bg-green-100 text-green-700':  p.estado==='pagado'||p.estado==='entregado',
                      'bg-blue-100 text-blue-700':    p.estado==='preparando'||p.estado==='enviado',
                      'bg-red-100 text-red-700':      p.estado==='cancelado'
                    }"
                    x-text="p.estado"></span>
              <p class="font-black text-mt-orange" x-text="formatPrecio(p.total)"></p>
              <i class="fas fa-chevron-right text-slate-300 text-xs"></i>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal detalle pedido cliente -->
    <div x-show="pedidoClienteDetalle" x-cloak @click.self="pedidoClienteDetalle=null"
         class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
      <div class="bg-white rounded-[2rem] w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl p-6">
        <template x-if="pedidoClienteDetalle">
          <div>
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-black text-mt-brown" x-text="'Pedido #'+pedidoClienteDetalle.id"></h3>
              <button @click="pedidoClienteDetalle=null" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times text-xl"></i>
              </button>
            </div>
            <div class="p-3 rounded-xl mb-4 text-center"
                 :class="{
                   'bg-yellow-50 text-yellow-700': pedidoClienteDetalle.estado==='pendiente',
                   'bg-green-50 text-green-700':   pedidoClienteDetalle.estado==='pagado'||pedidoClienteDetalle.estado==='entregado',
                   'bg-blue-50 text-blue-700':     pedidoClienteDetalle.estado==='preparando'||pedidoClienteDetalle.estado==='enviado',
                   'bg-red-50 text-red-700':       pedidoClienteDetalle.estado==='cancelado'
                 }">
              <p class="font-black uppercase text-sm" x-text="pedidoClienteDetalle.estado"></p>
            </div>
            <div class="space-y-2 mb-4">
              <template x-for="item in pedidoClienteDetalle.items" :key="item.id">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                  <img :src="item.imagen_url||'/mascotiendas/assets/no-image.png'"
                       class="w-10 h-10 object-cover rounded-lg flex-shrink-0">
                  <p class="flex-grow font-bold text-mt-brown text-sm line-clamp-1" x-text="item.nombre"></p>
                  <span class="text-xs text-slate-400 font-bold" x-text="'x'+item.cantidad"></span>
                  <span class="font-black text-mt-orange text-sm" x-text="formatPrecio(item.precio*item.cantidad)"></span>
                </div>
              </template>
            </div>
            <div class="flex justify-between font-black text-mt-brown p-4 bg-mt-cream rounded-xl mb-3">
              <span>Total</span>
              <span class="text-mt-orange" x-text="formatPrecio(pedidoClienteDetalle.total)"></span>
            </div>
            <div class="text-xs text-slate-500 space-y-1">
              <p x-show="pedidoClienteDetalle.direccion">
                <i class="fas fa-map-marker-alt text-mt-orange mr-1"></i>
                <span x-text="pedidoClienteDetalle.direccion+', '+pedidoClienteDetalle.ciudad"></span>
              </p>
              <p x-show="pedidoClienteDetalle.metodo_pago">
                <i class="fas fa-credit-card text-mt-orange mr-1"></i>
                <span x-text="pedidoClienteDetalle.metodo_pago"></span>
              </p>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

</div>
