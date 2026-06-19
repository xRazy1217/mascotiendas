<div x-show="seccion==='marketing'" x-cloak>
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-mt-brown uppercase">Marketing & Fidelización</h2>
    <span class="px-3 py-1 bg-mt-cream text-mt-brown rounded-full text-xs font-black">Módulo Interno</span>
  </div>

  <!-- Menú de Sub-Pestañas -->
  <div class="flex flex-wrap gap-2 border-b border-mt-cream mb-6">
    <button @click="seccionMarketingTab='resumen'"
            :class="seccionMarketingTab==='resumen'?'border-mt-orange text-mt-orange bg-white shadow-sm':'border-transparent text-slate-400 hover:text-mt-brown'"
            class="px-4 py-2 border-b-2 font-black text-xs transition-all uppercase rounded-t-xl flex items-center gap-2">
      <i class="fas fa-chart-pie"></i> Resumen
    </button>
    <button @click="seccionMarketingTab='suscriptores'"
            :class="seccionMarketingTab==='suscriptores'?'border-mt-orange text-mt-orange bg-white shadow-sm':'border-transparent text-slate-400 hover:text-mt-brown'"
            class="px-4 py-2 border-b-2 font-black text-xs transition-all uppercase rounded-t-xl flex items-center gap-2">
      <i class="fas fa-envelope-open-text"></i> Suscriptores
    </button>
    <button @click="seccionMarketingTab='carritos'"
            :class="seccionMarketingTab==='carritos'?'border-mt-orange text-mt-orange bg-white shadow-sm':'border-transparent text-slate-400 hover:text-mt-brown'"
            class="px-4 py-2 border-b-2 font-black text-xs transition-all uppercase rounded-t-xl flex items-center gap-2">
      <i class="fas fa-shopping-cart"></i> Carritos Abandonados
    </button>
    <button @click="seccionMarketingTab='nueva_campana'"
            :class="seccionMarketingTab==='nueva_campana'?'border-mt-orange text-mt-orange bg-white shadow-sm':'border-transparent text-slate-400 hover:text-mt-brown'"
            class="px-4 py-2 border-b-2 font-black text-xs transition-all uppercase rounded-t-xl flex items-center gap-2">
      <i class="fas fa-paper-plane"></i> Enviar Campaña
    </button>
    <button @click="seccionMarketingTab='blacklist';cargarBlacklist()"
            :class="seccionMarketingTab==='blacklist'?'border-mt-orange text-mt-orange bg-white shadow-sm':'border-transparent text-slate-400 hover:text-mt-brown'"
            class="px-4 py-2 border-b-2 font-black text-xs transition-all uppercase rounded-t-xl flex items-center gap-2">
      <i class="fas fa-user-slash"></i> Lista de Exclusión (Blacklist)
    </button>
  </div>

  <!-- ─── SUB-PESTAÑA: RESUMEN ─── -->
  <div x-show="seccionMarketingTab==='resumen'" class="space-y-6">
    <!-- KPIs Rediseñados -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Suscriptores Activos</p>
          <i class="fas fa-user-check text-mt-orange opacity-60"></i>
        </div>
        <p class="text-3xl font-black text-mt-brown" x-text="listaNewsletter.filter(s=>s.estado==='activo').length"></p>
        <p class="text-[10px] text-slate-400 mt-1" x-text="'Total registros: '+listaNewsletter.length"></p>
      </div>

      <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Carritos Abandonados</p>
          <i class="fas fa-shopping-bag text-red-500 opacity-60"></i>
        </div>
        <p class="text-3xl font-black text-red-500" x-text="listaCarritos.filter(c=>c.estado==='abandonado').length"></p>
        <p class="text-[10px] text-slate-400 mt-1" x-text="'Monitoreando '+listaCarritos.length+' sesiones'"></p>
      </div>

      <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Carritos Activos</p>
          <i class="fas fa-bolt text-emerald-500 opacity-60"></i>
        </div>
        <p class="text-3xl font-black text-emerald-500" x-text="listaCarritos.filter(c=>c.estado==='activo').length"></p>
        <p class="text-[10px] text-slate-400 mt-1">en tiempo real</p>
      </div>

      <div class="bg-white p-5 rounded-2xl shadow-sm border border-mt-cream">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Cupones de Descuento</p>
          <i class="fas fa-tags text-mt-orange opacity-60"></i>
        </div>
        <p class="text-3xl font-black text-mt-brown" x-text="listaCupones.filter(c=>c.activo).length"></p>
        <p class="text-[10px] text-slate-400 mt-1" x-text="'Total creados: '+listaCupones.length"></p>
      </div>
    </div>

    <!-- Resumen de Actividad -->
    <div class="grid lg:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-mt-cream">
        <h3 class="font-black text-mt-brown mb-4 flex items-center gap-2"><i class="fas fa-chart-line text-mt-orange"></i> Conversión de Carritos</h3>
        <div class="space-y-4">
          <p class="text-sm text-slate-500">Monitoreo de comportamiento en el embudo de ventas actual.</p>
          <div class="relative pt-1">
            <div class="flex mb-2 items-center justify-between">
              <div>
                <span class="text-xs font-bold inline-block py-1 px-2 uppercase rounded-full text-mt-brown bg-mt-cream">Tasa de Abandono</span>
              </div>
              <div class="text-right">
                <span class="text-xs font-bold inline-block text-mt-brown"
                      x-text="listaCarritos.length ? Math.round((listaCarritos.filter(c=>c.estado==='abandonado').length/listaCarritos.length)*100)+'%' : '0%'"></span>
              </div>
            </div>
            <div class="overflow-hidden h-2 text-xs flex rounded-full bg-slate-100">
              <div :style="'width:'+(listaCarritos.length ? (listaCarritos.filter(c=>c.estado==='abandonado').length/listaCarritos.length)*100 : 0)+'%'"
                   class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-red-400 rounded-full transition-all"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-2xl shadow-sm border border-mt-cream">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-black text-mt-brown flex items-center gap-2"><i class="fas fa-server text-mt-orange"></i> Automatización</h3>
          <button @click="toggleConfig('marketing_automatizacion_activo')"
                  :class="config.marketing_automatizacion_activo==='1' ? 'bg-mt-orange' : 'bg-slate-300'"
                  class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none">
            <span :class="config.marketing_automatizacion_activo==='1' ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-200"></span>
          </button>
        </div>
        <p class="text-sm text-slate-500 mb-4">El Cron Job automático revisa la base de datos cada hora para enviar correos de recuperación a las 2 y 24 horas.</p>
        <div class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-100 rounded-xl">
          <i class="fas fa-info-circle text-mt-orange text-lg flex-shrink-0"></i>
          <p class="text-xs text-yellow-800 leading-relaxed">Asegúrate de que la tarea cron de tu servidor esté apuntando a: <br><code>api/cron_carritos.php</code></p>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── SUB-PESTAÑA: SUSCRIPTORES ─── -->
  <div x-show="seccionMarketingTab==='suscriptores'" class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <div class="p-6 border-b border-mt-cream flex flex-col md:flex-row justify-between items-center gap-4">
      <div>
        <h3 class="font-black text-mt-brown">Lista de Suscriptores</h3>
        <p class="text-xs text-slate-400">Usuarios inscritos en el boletín de novedades de la tienda.</p>
      </div>
      <div class="flex gap-2 w-full md:w-auto">
        <button @click="exportarNewsletter()"
                class="px-4 py-2 bg-mt-cream text-mt-brown hover:bg-mt-orange hover:text-white transition-colors rounded-xl font-bold text-xs flex items-center gap-2">
          <i class="fas fa-download"></i> Exportar CSV
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-slate-400 font-black text-[10px] uppercase tracking-wider border-b border-slate-100">
            <th class="p-4">Email</th>
            <th class="p-4">Nombre</th>
            <th class="p-4">Estado</th>
            <th class="p-4">Registro</th>
            <th class="p-4 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="s in listaNewsletter" :key="s.id">
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="p-4 text-sm font-bold text-mt-brown" x-text="s.email"></td>
              <td class="p-4 text-sm text-slate-600" x-text="s.nombre || '-'"></td>
              <td class="p-4">
                <span :class="s.estado === 'activo' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                      x-text="s.estado"></span>
              </td>
              <td class="p-4 text-xs text-slate-400" x-text="new Date(s.fecha_registro || s.creado_en).toLocaleString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})"></td>
              <td class="p-4 text-right space-x-1">
                <button @click="toggleSuscriptor(s.id)"
                        :title="s.estado === 'activo' ? 'Desactivar' : 'Activar'"
                        class="p-1 px-2.5 bg-slate-100 text-slate-600 hover:bg-mt-orange hover:text-white transition-colors rounded-lg text-xs font-bold">
                  <i :class="s.estado === 'activo' ? 'fas fa-ban' : 'fas fa-check'"></i>
                </button>
                <button @click="eliminarSuscriptor(s.id)"
                        title="Eliminar"
                        class="p-1 px-2.5 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors rounded-lg text-xs font-bold">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </template>
          <template x-if="listaNewsletter.length===0">
            <tr>
              <td colspan="5" class="p-8 text-center text-slate-400 italic text-sm">No hay suscriptores registrados aún.</td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ─── SUB-PESTAÑA: CARRITOS ─── -->
  <div x-show="seccionMarketingTab==='carritos'" class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <div class="p-6 border-b border-mt-cream flex justify-between items-center">
      <div>
        <h3 class="font-black text-mt-brown">Carritos Abandonados e Inactivos</h3>
        <p class="text-xs text-slate-400">Clientes que añadieron productos al carrito pero no completaron la compra.</p>
      </div>
      <button @click="cargarCarritos()" class="p-2 text-mt-orange hover:bg-mt-cream rounded-xl transition-colors font-bold text-xs flex items-center gap-2">
        <i class="fas fa-sync-alt"></i> Actualizar
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-slate-400 font-black text-[10px] uppercase tracking-wider border-b border-slate-100">
            <th class="p-4">Cliente / Sesión</th>
            <th class="p-4">Productos en Carrito</th>
            <th class="p-4">Total</th>
            <th class="p-4">Estado</th>
            <th class="p-4">Recordatorios</th>
            <th class="p-4">Última Actividad</th>
            <th class="p-4 text-right">Recuperación Manual</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="c in listaCarritos" :key="c.id">
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="p-4">
                <div class="max-w-[200px]">
                  <p class="text-sm font-bold text-mt-brown truncate" x-text="c.user_nombre || c.email_invitado || 'Invitado no identificado'"></p>
                  <p class="text-[10px] text-slate-400 truncate" x-text="c.user_email || c.email_invitado || ('Sesión: '+c.token_sesion.substring(0,8)+'...')"></p>
                </div>
              </td>
              <td class="p-4">
                <div class="space-y-1 max-w-[250px] overflow-hidden">
                  <template x-for="item in JSON.parse(c.datos_carrito || '[]')" :key="item.id">
                    <div class="text-xs text-slate-600 truncate flex items-center gap-1.5">
                      <span class="bg-mt-cream text-mt-brown font-black px-1 py-0.2 rounded text-[9px]" x-text="item.cantidad+'x'"></span>
                      <span class="truncate" x-text="item.nombre"></span>
                    </div>
                  </template>
                </div>
              </td>
              <td class="p-4 font-black text-sm text-mt-brown" x-text="formatPrecio(JSON.parse(c.datos_carrito || '[]').reduce((acc, curr) => acc + (curr.precio * curr.cantidad), 0))"></td>
              <td class="p-4">
                <span :class="{
                        'bg-red-50 text-red-600 border border-red-100': c.estado==='abandonado',
                        'bg-blue-50 text-blue-600 border border-blue-100': c.estado==='activo',
                        'bg-emerald-50 text-emerald-600 border border-emerald-100': c.estado==='recuperado' || c.estado==='completado'
                      }"
                      class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                      x-text="c.estado"></span>
              </td>
              <td class="p-4">
                <div class="flex flex-col gap-1">
                  <span class="text-[9px] font-bold flex items-center gap-1" :class="c.recordatorio_1_enviado === '1' || c.recordatorio_1_enviado === 1 || c.recordatorio_1_enviado ? 'text-emerald-600' : 'text-slate-400'">
                    <i :class="c.recordatorio_1_enviado === '1' || c.recordatorio_1_enviado === 1 || c.recordatorio_1_enviado ? 'fas fa-check-circle' : 'far fa-circle'"></i> Rec. 1 (2h)
                  </span>
                  <span class="text-[9px] font-bold flex items-center gap-1" :class="c.recordatorio_2_enviado === '1' || c.recordatorio_2_enviado === 1 || c.recordatorio_2_enviado ? 'text-emerald-600' : 'text-slate-400'">
                    <i :class="c.recordatorio_2_enviado === '1' || c.recordatorio_2_enviado === 1 || c.recordatorio_2_enviado ? 'fas fa-check-circle' : 'far fa-circle'"></i> Rec. 2 (24h)
                  </span>
                </div>
              </td>
              <td class="p-4 text-xs text-slate-400" x-text="new Date(c.fecha_actualizacion).toLocaleString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})"></td>
              <td class="p-4 text-right">
                <div class="flex justify-end gap-1.5" x-show="c.user_email || c.email_invitado">
                  <button @click="enviarRecordatorioManual(c.id, 1)"
                          class="px-2 py-1 bg-mt-cream text-mt-brown hover:bg-mt-orange hover:text-white rounded-lg text-[10px] font-black transition-colors">
                    Enviar R1
                  </button>
                  <button @click="enviarRecordatorioManual(c.id, 2)"
                          class="px-2 py-1 bg-mt-orange text-white hover:bg-mt-brown rounded-lg text-[10px] font-black transition-colors">
                    Enviar R2
                  </button>
                </div>
                <span class="text-xs text-slate-400 italic" x-show="!c.user_email && !c.email_invitado">Sin email</span>
              </td>
            </tr>
          </template>
          <template x-if="listaCarritos.length===0">
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-400 italic text-sm">No hay carritos inactivos monitoreados en la sesión.</td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ─── SUB-PESTAÑA: ENVIAR CAMPAÑA ─── -->
  <div x-show="seccionMarketingTab==='nueva_campana'" class="bg-white rounded-2xl shadow-sm border border-mt-cream p-6">
    <div class="mb-6">
      <h3 class="font-black text-mt-brown">Enviar Boletín Masivo</h3>
      <p class="text-xs text-slate-400">Escribe y envía un correo a todos tus suscriptores del boletín que tengan estado "activo".</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
      <!-- Formulario -->
      <div class="lg:col-span-2 space-y-4">
        <div>
          <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Asunto del Correo</label>
          <input type="text" x-model="campanaAsunto" placeholder="Ej: ¡Sorpresa para tu peludo! 🐾 10% de descuento este fin de semana"
                 class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-mt-orange text-sm font-semibold">
        </div>

        <div>
          <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">Cuerpo del Mensaje (HTML o Texto Plano)</label>
          <textarea x-model="campanaMensaje" rows="12" placeholder="Escribe el contenido de tu boletín aquí. Puedes utilizar etiquetas HTML básicas para dar formato."
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-mt-orange text-sm font-medium font-sans resize-none"></textarea>
        </div>

        <div class="pt-2">
          <button @click="enviarCampanaMasiva()"
                  class="w-full md:w-auto px-6 py-3 bg-mt-orange text-white hover:bg-mt-brown transition-colors rounded-xl font-black text-sm flex items-center justify-center gap-2 shadow-md">
            <i class="fas fa-paper-plane"></i> Iniciar Envío Masivo
          </button>
        </div>
      </div>

      <!-- Ayuda y placeholders -->
      <div class="space-y-4">
        <div class="bg-mt-cream p-5 rounded-2xl border border-mt-cream/20">
          <h4 class="font-black text-mt-brown text-sm mb-3 flex items-center gap-2"><i class="fas fa-magic text-mt-orange"></i> Etiquetas Mágicas</h4>
          <p class="text-xs text-slate-600 leading-relaxed mb-3">Puedes usar el siguiente comodín en el texto del mensaje. El sistema lo reemplazará por el nombre correspondiente de cada suscriptor:</p>
          <ul class="space-y-2 text-xs text-mt-brown font-bold">
            <li class="flex items-center gap-2"><code class="bg-white px-2 py-0.5 rounded border border-mt-cream">{nombre}</code> <span>Nombre del destinatario</span></li>
          </ul>
        </div>

        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
          <h4 class="font-black text-slate-700 text-sm mb-2 flex items-center gap-2"><i class="fas fa-exclamation-triangle text-amber-500"></i> Buenas Prácticas</h4>
          <p class="text-xs text-slate-500 leading-relaxed">
            - Evita escribir el asunto completamente en MAYÚSCULAS para no caer en la bandeja de SPAM.<br><br>
            - Se incluirá un enlace automático al final de cada correo que permitirá a los usuarios desuscribirse con un solo clic.
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── SUB-PESTAÑA: BLACKLIST (LISTA DE EXCLUSIÓN) ─── -->
  <div x-show="seccionMarketingTab==='blacklist'" class="bg-white rounded-2xl shadow-sm border border-mt-cream overflow-hidden">
    <div class="p-6 border-b border-mt-cream">
      <h3 class="font-black text-mt-brown">Lista de Exclusión de Correos (Blacklist)</h3>
      <p class="text-xs text-slate-400">Direcciones de correo de clientes o invitados que han solicitado no recibir correos automáticos de marketing ni recordatorios de carritos abandonados.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-slate-400 font-black text-[10px] uppercase tracking-wider border-b border-slate-100">
            <th class="p-4">Email</th>
            <th class="p-4">Fecha de Solicitud</th>
            <th class="p-4 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template x-for="b in listaBlacklist" :key="b.id">
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="p-4 text-sm font-bold text-mt-brown" x-text="b.email"></td>
              <td class="p-4 text-xs text-slate-400" x-text="new Date(b.fecha_desuscripcion).toLocaleString('es-CL',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})"></td>
              <td class="p-4 text-right">
                <button @click="eliminarBlacklist(b.id)"
                        title="Remover de la lista negra"
                        class="p-1.5 px-3 bg-red-50 text-red-600 hover:bg-red-500 hover:text-white transition-colors rounded-xl text-xs font-black flex items-center gap-1.5 ml-auto">
                  <i class="fas fa-trash-alt"></i> Re-activar
                </button>
              </td>
            </tr>
          </template>
          <tr x-show="!listaBlacklist.length">
            <td colspan="3" class="p-8 text-center text-sm text-slate-400 font-medium">
              <i class="fas fa-user-check text-2xl mb-2 text-slate-300 block"></i>
              No hay correos en la lista de exclusión.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
