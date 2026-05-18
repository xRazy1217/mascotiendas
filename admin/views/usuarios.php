<div x-show="seccion==='usuarios'" x-cloak>
  <h2 class="text-2xl font-black text-mt-brown mb-6 uppercase">Usuarios</h2>
  <div class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-mt-cream">
        <tr>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Usuario</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Email</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase">Rol</th>
          <th class="text-left px-4 py-3 font-black text-mt-brown text-xs uppercase hidden md:table-cell">Registro</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="u in listaUsuarios" :key="u.id">
          <tr class="border-t border-mt-cream hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3">
              <p class="font-bold text-mt-brown" x-text="u.nombre+' '+u.apellido"></p>
            </td>
            <td class="px-4 py-3 text-slate-500 hidden md:table-cell" x-text="u.email"></td>
            <td class="px-4 py-3">
              <span :class="u.rol==='admin'?'bg-mt-orange text-white':'bg-mt-cream text-mt-brown'"
                    class="px-2 py-0.5 rounded-full text-xs font-black uppercase" x-text="u.rol"></span>
            </td>
            <td class="px-4 py-3 text-xs text-slate-400 hidden md:table-cell"
                x-text="new Date(u.creado_en).toLocaleDateString('es-CL')"></td>
          </tr>
        </template>
      </tbody>
    </table>
    <div x-show="listaUsuarios.length===0" class="text-center py-12 text-slate-400 italic">No hay usuarios</div>
  </div>
</div>
