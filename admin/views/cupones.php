<div x-show="seccion==='cupones'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Cupones</h2>
    <button @click="abrirFormCupon(null)"
            class="px-4 py-2 bg-mt-orange text-white rounded-xl font-bold text-sm hover:bg-orange-500 transition-colors">
      <i class="fas fa-plus mr-1"></i> Nuevo Cupón
    </button>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Código</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Descuento</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Usos</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Expira</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Estado</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="c in listaCupones" :key="c.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3">
              <span class="font-black text-mt-brown bg-mt-cream px-3 py-1 rounded-lg text-sm tracking-widest" x-text="c.codigo"></span>
            </td>
            <td class="px-4 py-3 font-bold text-mt-brown">
              <span x-text="c.tipo==='porcentaje' ? c.valor+'%' : '$'+Number(c.valor).toLocaleString('es-CL')"></span>
              <span x-show="c.minimo_compra>0" class="text-xs text-slate-400 ml-1"
                    x-text="'(min. $'+Number(c.minimo_compra).toLocaleString('es-CL')+')'"></span>
            </td>
            <td class="px-4 py-3 text-slate-500 hidden md:table-cell"
                x-text="c.usos_actual + (c.usos_max ? ' / '+c.usos_max : ' / ∞')"></td>
            <td class="px-4 py-3 text-slate-500 hidden md:table-cell"
                x-text="c.expira_en || 'Sin vencimiento'"></td>
            <td class="px-4 py-3">
              <span :class="c.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                    class="px-2 py-0.5 rounded-full text-xs font-black uppercase"
                    x-text="c.activo ? 'Activo' : 'Inactivo'"></span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="abrirFormCupon(c)"
                        class="px-3 py-1.5 bg-mt-cream text-mt-brown rounded-lg font-bold text-xs hover:bg-mt-orange hover:text-white transition-colors">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="eliminarCupon(c.id)"
                        class="px-3 py-1.5 bg-red-50 text-red-500 rounded-lg font-bold text-xs hover:bg-red-500 hover:text-white transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaCupones.length===0" class="text-center py-12 text-slate-400 italic">No hay cupones</div>
  </div>

  <!-- Modal cupón -->
  <div x-show="formCupon" x-cloak @click.self="formCupon=null"
       class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-black text-mt-brown" x-text="formCupon?.id ? 'Editar Cupón' : 'Nuevo Cupón'"></h3>
        <button @click="formCupon=null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
      </div>
      <template x-if="formCupon">
        <div class="space-y-3">
          <input x-model="formCupon.codigo" placeholder="CODIGO *" style="text-transform:uppercase"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-black text-sm tracking-widest">
          <div class="grid grid-cols-2 gap-3">
            <select x-model="formCupon.tipo" class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none font-bold text-sm bg-white">
              <option value="porcentaje">Porcentaje (%)</option>
              <option value="monto_fijo">Monto fijo ($)</option>
            </select>
            <input x-model="formCupon.valor" type="number" :placeholder="formCupon.tipo==='porcentaje'?'Ej: 10 (10%)':'Ej: 5000'"
                   class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          </div>
          <input x-model="formCupon.minimo_compra" type="number" placeholder="Mínimo de compra (0 = sin mínimo)"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <input x-model="formCupon.usos_max" type="number" placeholder="Usos máximos (vacío = ilimitado)"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <input x-model="formCupon.expira_en" type="date"
                 class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <label class="flex items-center gap-2 font-bold text-sm cursor-pointer">
            <input type="checkbox" x-model="formCupon.activo" class="w-4 h-4 accent-mt-orange"> Activo
          </label>
          <div class="flex gap-3 pt-2">
            <button @click="guardarCupon()"
                    class="flex-grow bg-mt-orange text-white py-3 rounded-2xl font-black uppercase hover:bg-orange-500 transition-colors">
              Guardar
            </button>
            <button @click="formCupon=null"
                    class="px-6 py-3 bg-mt-cream text-mt-brown rounded-2xl font-black hover:bg-slate-200 transition-colors">
              Cancelar
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
