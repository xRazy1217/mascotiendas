<div x-show="seccion==='notificaciones'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Notificaciones</h2>
    <button @click="leerTodasNotif()"
            class="px-4 py-2 bg-mt-cream text-mt-brown rounded-xl font-bold text-sm hover:bg-mt-orange hover:text-white transition-colors">
      Marcar todas como leídas
    </button>
  </div>
  <div class="space-y-3">
    <template x-for="n in listaNotif" :key="n.id">
      <div @click="leerNotif(n)"
           :class="n.leida ? 'bg-white opacity-60' : 'bg-white border-l-4 border-mt-orange'"
           class="p-4 rounded-2xl shadow-sm border border-mt-cream cursor-pointer hover:shadow-md transition-all">
        <div class="flex items-start gap-3">
          <div :class="{
                 'bg-red-100 text-red-500': n.tipo==='stock',
                 'bg-blue-100 text-blue-500': n.tipo==='pedido',
                 'bg-green-100 text-green-500': n.tipo==='pago',
                 'bg-mt-cream text-mt-orange': !['stock','pedido','pago'].includes(n.tipo)
               }"
               class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0">
            <i :class="{
                 'fas fa-box-open': n.tipo==='stock',
                 'fas fa-shopping-bag': n.tipo==='pedido',
                 'fas fa-check-circle': n.tipo==='pago',
                 'fas fa-bell': !['stock','pedido','pago'].includes(n.tipo)
               }" class="text-sm"></i>
          </div>
          <div class="flex-grow">
            <p class="font-black text-mt-brown text-sm" x-text="n.titulo"></p>
            <p class="text-xs text-slate-500 mt-0.5" x-text="n.mensaje"></p>
            <p class="text-xs text-slate-400 mt-1" x-text="new Date(n.creado_en).toLocaleString('es-CL')"></p>
          </div>
          <span x-show="!n.leida" class="w-2 h-2 bg-mt-orange rounded-full flex-shrink-0 mt-1"></span>
        </div>
      </div>
    </template>
    <template x-if="listaNotif.length===0">
      <div class="text-center py-16 text-slate-400">
        <i class="fas fa-bell-slash text-4xl mb-3 opacity-30"></i>
        <p class="font-bold italic">Sin notificaciones</p>
      </div>
    </template>
  </div>
</div>
