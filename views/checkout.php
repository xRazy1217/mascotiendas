<div x-show="page==='checkout'" x-cloak class="fade-in max-w-2xl mx-auto">
  <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-mt-cream">
    <h2 class="text-2xl font-black text-mt-brown mb-6 uppercase text-center">Finalizar Compra</h2>

    <form @submit.prevent="realizarPedido()" class="space-y-4">

      <!-- Datos personales -->
      <p class="font-black text-mt-brown text-xs uppercase tracking-widest">Datos de contacto</p>
      <div class="grid grid-cols-2 gap-3">
        <input x-model="checkout.nombre" required placeholder="Nombre completo *"
               class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        <input x-model="checkout.telefono" placeholder="Teléfono"
               class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      </div>
      <input x-model="checkout.email" required type="email" placeholder="Email *"
             class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">

      <!-- Entrega -->
      <p class="font-black text-mt-brown text-xs uppercase tracking-widest pt-2">Método de entrega</p>
      <div class="grid grid-cols-2 gap-3">
        <button type="button" @click="checkout.metodo_entrega='delivery'"
                :class="checkout.metodo_entrega==='delivery'?'border-mt-orange bg-mt-cream':'border-mt-cream'"
                class="p-4 rounded-2xl border-2 text-center transition-all">
          <i class="fas fa-truck text-mt-orange text-xl mb-1 block"></i>
          <p class="font-black text-mt-brown text-sm">Delivery</p>
          <p class="text-xs text-slate-400">Zona urbana</p>
        </button>
        <button type="button" @click="checkout.metodo_entrega='retiro'"
                :class="checkout.metodo_entrega==='retiro'?'border-mt-orange bg-mt-cream':'border-mt-cream'"
                class="p-4 rounded-2xl border-2 text-center transition-all">
          <i class="fas fa-store text-mt-orange text-xl mb-1 block"></i>
          <p class="font-black text-mt-brown text-sm">Retiro en tienda</p>
          <p class="text-xs text-slate-400">Sin costo</p>
        </button>
      </div>

      <!-- Direccion (solo delivery) -->
      <div x-show="checkout.metodo_entrega==='delivery'" class="space-y-3">
        <input x-model="checkout.direccion" required placeholder="Dirección completa *"
               class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        <div class="grid grid-cols-2 gap-3">
          <input x-model="checkout.ciudad" required placeholder="Ciudad *"
                 class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <select x-model="checkout.zona_id" class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none font-bold text-sm bg-white">
            <option value="">Zona de delivery</option>
            <template x-for="z in zonas" :key="z.id">
              <option :value="z.id" x-text="z.nombre+(z.costo>0?' (+'+formatPrecio(z.costo)+')':' (Gratis)')"></option>
            </template>
          </select>
        </div>
        <!-- Alerta zona -->
        <div x-show="checkout.zona_id" class="p-3 rounded-xl text-xs font-bold"
             :class="zonas.find(z=>z.id==checkout.zona_id)?.costo>0?'bg-yellow-50 text-yellow-700 border border-yellow-200':'bg-green-50 text-green-700 border border-green-200'">
          <template x-if="zonas.find(z=>z.id==checkout.zona_id)?.costo>0">
            <span><i class="fas fa-info-circle mr-1"></i>Esta zona tiene costo de delivery de <span x-text="formatPrecio(zonas.find(z=>z.id==checkout.zona_id)?.costo)"></span></span>
          </template>
          <template x-if="zonas.find(z=>z.id==checkout.zona_id)?.costo===0||zonas.find(z=>z.id==checkout.zona_id)?.costo==='0'">
            <span><i class="fas fa-check-circle mr-1"></i>¡Delivery gratis en tu zona!</span>
          </template>
        </div>
      </div>

      <!-- Sucursal retiro -->
      <div x-show="checkout.metodo_entrega==='retiro'">
        <select x-model="checkout.sucursal_retiro" class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none font-bold text-sm bg-white">
          <option value="Balmaceda">Av. Balmaceda 4521 Local #2, La Serena</option>
          <option value="Geronimo">Gerónimo Méndez, Coquimbo</option>
          <option value="Alessandri">Alessandri 147, El Llano</option>
        </select>
      </div>

      <!-- Horario deseado -->
      <p class="font-black text-mt-brown text-xs uppercase tracking-widest pt-2">Horario preferido</p>
      <select x-model="checkout.horario" class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none font-bold text-sm bg-white">
        <option value="">Selecciona un horario</option>
        <option value="10:00 - 12:00">10:00 - 12:00</option>
        <option value="12:00 - 14:00">12:00 - 14:00</option>
        <option value="14:00 - 16:00">14:00 - 16:00</option>
        <option value="16:00 - 18:00">16:00 - 18:00</option>
        <option value="18:00 - 20:00">18:00 - 20:00</option>
        <option value="Primera hora del dia siguiente">Primera hora del día siguiente</option>
      </select>

      <!-- Metodo de pago -->
      <p class="font-black text-mt-brown text-xs uppercase tracking-widest pt-2">Método de pago</p>
      <div class="grid grid-cols-3 gap-3">
        <button type="button" @click="checkout.metodo_pago='transferencia'"
                :class="checkout.metodo_pago==='transferencia'?'border-mt-orange bg-mt-cream':'border-mt-cream'"
                class="p-3 rounded-2xl border-2 text-center transition-all">
          <i class="fas fa-university text-mt-orange text-lg mb-1 block"></i>
          <p class="font-black text-mt-brown text-xs">Transferencia</p>
        </button>
        <button type="button" @click="checkout.metodo_pago='efectivo'"
                :class="checkout.metodo_pago==='efectivo'?'border-mt-orange bg-mt-cream':'border-mt-cream'"
                class="p-3 rounded-2xl border-2 text-center transition-all">
          <i class="fas fa-money-bill-wave text-mt-orange text-lg mb-1 block"></i>
          <p class="font-black text-mt-brown text-xs">Efectivo</p>
        </button>
        <button type="button" @click="checkout.metodo_pago='webpay'"
                :class="checkout.metodo_pago==='webpay'?'border-mt-orange bg-mt-cream':'border-mt-cream'"
                class="p-3 rounded-2xl border-2 text-center transition-all opacity-50 cursor-not-allowed" disabled>
          <i class="fas fa-credit-card text-mt-orange text-lg mb-1 block"></i>
          <p class="font-black text-mt-brown text-xs">Webpay</p>
          <p class="text-[9px] text-slate-400">Próximamente</p>
        </button>
      </div>

      <!-- Info transferencia -->
      <div x-show="checkout.metodo_pago==='transferencia'"
           class="p-4 bg-blue-50 border border-blue-200 rounded-2xl text-sm space-y-1">
        <p class="font-black text-blue-700 text-xs uppercase mb-2">Datos para transferencia</p>
        <p class="font-bold text-blue-600">Banco: <span class="font-normal">Banco Estado</span></p>
        <p class="font-bold text-blue-600">Cuenta: <span class="font-normal">Cuenta RUT</span></p>
        <p class="font-bold text-blue-600">RUT: <span class="font-normal">12.345.678-9</span></p>
        <p class="font-bold text-blue-600">Nombre: <span class="font-normal">Mascotiendas SpA</span></p>
        <p class="font-bold text-blue-600">Email: <span class="font-normal">ventas@mascotiendas.cl</span></p>
        <p class="text-xs text-blue-500 mt-2">Envía el comprobante por WhatsApp al +569 5379 3135</p>
      </div>

      <!-- Info efectivo -->
      <div x-show="checkout.metodo_pago==='efectivo'"
           class="p-4 bg-green-50 border border-green-200 rounded-2xl text-sm">
        <p class="font-black text-green-700 text-xs uppercase mb-1">Pago en efectivo</p>
        <p class="text-green-600 text-xs">El pago se realiza al momento de la entrega o retiro en tienda.</p>
      </div>

      <!-- Cupón -->
      <div x-data="{codigoCupon:'',cuponAplicado:null,cuponError:''}" class="space-y-2">
        <p class="font-black text-mt-brown text-xs uppercase tracking-widest pt-2">Cupón de descuento</p>
        <div class="flex gap-2">
          <input x-model="codigoCupon" placeholder="Código de cupón" style="text-transform:uppercase"
                 class="flex-grow px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
          <button type="button" @click="aplicarCupon(codigoCupon, cartTotal(), (r)=>{cuponAplicado=r;cuponError='';checkout.cupon=codigoCupon;checkout.descuento=r.descuento}, (e)=>{cuponError=e;cuponAplicado=null})"
                  class="px-4 py-3 bg-mt-brown text-white rounded-xl font-black text-sm hover:bg-mt-orange transition-colors">
            Aplicar
          </button>
        </div>
        <p x-show="cuponError" x-text="cuponError" class="text-red-500 text-xs font-bold"></p>
        <div x-show="cuponAplicado" class="p-3 bg-green-50 border border-green-200 rounded-xl text-xs font-bold text-green-700">
          <i class="fas fa-check-circle mr-1"></i>
          Cupón aplicado: descuento de <span x-text="cuponAplicado?formatPrecio(cuponAplicado.descuento):''"></span>
        </div>
      </div>

      <textarea x-model="checkout.notas" placeholder="Notas adicionales (opcional)" rows="2"
                class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm resize-none"></textarea>

      <!-- Resumen -->
      <div class="bg-mt-cream rounded-2xl p-4 space-y-2">
        <template x-for="item in cart" :key="item.id">
          <div class="flex justify-between text-sm font-bold text-mt-brown">
            <span x-text="item.nombre+' x'+item.cantidad" class="truncate mr-2"></span>
            <span x-text="formatPrecio(item.precio*item.cantidad)" class="flex-shrink-0"></span>
          </div>
        </template>
        <!-- Costo delivery -->
        <div x-show="checkout.metodo_entrega==='delivery' && checkout.zona_id && zonas.find(z=>z.id==checkout.zona_id)?.costo>0"
             class="flex justify-between text-sm font-bold text-mt-brown border-t border-mt-brown/10 pt-2">
          <span>Delivery</span>
          <span x-text="formatPrecio(zonas.find(z=>z.id==checkout.zona_id)?.costo||0)"></span>
        </div>
        <!-- Descuento cupon -->
        <div x-show="checkout.descuento>0"
             class="flex justify-between text-sm font-bold text-green-600 border-t border-mt-brown/10 pt-2">
          <span>Descuento cupón</span>
          <span x-text="'-'+formatPrecio(checkout.descuento||0)"></span>
        </div>
        <div class="border-t border-mt-brown/20 pt-2 flex justify-between font-black text-mt-brown text-lg">
          <span>Total:</span>
          <span class="text-mt-orange" x-text="formatPrecio(totalConDescuento())"></span>
        </div>
      </div>

      <p x-show="checkoutError" x-text="checkoutError" class="text-red-500 text-sm font-bold text-center"></p>

      <!-- Aviso horario -->
      <div class="p-3 rounded-xl text-xs font-bold"
           :class="new Date().getHours() >= 20 ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : 'bg-green-50 text-green-700 border border-green-200'">
        <template x-if="new Date().getHours() >= 20">
          <span><i class="fas fa-clock mr-1"></i>Son más de las 20:00 hrs. Tu pedido será despachado mañana por la mañana.</span>
        </template>
        <template x-if="new Date().getHours() < 20">
          <span><i class="fas fa-check-circle mr-1"></i>Tu pedido será despachado hoy.</span>
        </template>
      </div>

      <!-- Descuento primer pedido -->
      <div x-show="config.descuento_primer_pedido === '1' && usuario"
           class="p-3 bg-orange-50 border border-orange-200 rounded-xl text-xs font-bold text-orange-700">
        <i class="fas fa-gift mr-1"></i>10% de descuento en tu primer pedido aplicado automáticamente.
      </div>

      <button type="submit" :disabled="!checkout.metodo_pago"
              class="w-full bg-green-500 text-white py-4 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-green-600 transition-colors disabled:opacity-60">
        <i class="fab fa-whatsapp mr-2 text-xl"></i>Confirmar por WhatsApp
      </button>
      <button type="button" @click="page='carrito'"
              class="w-full text-mt-brown font-bold text-sm hover:text-mt-orange transition-colors">
        ← Volver al carrito
      </button>
    </form>
  </div>
</div>
