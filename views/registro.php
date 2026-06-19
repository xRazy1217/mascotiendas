<div x-show="page==='registro'" x-cloak class="fade-in max-w-md mx-auto">
  <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-mt-cream">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-mt-orange rounded-2xl flex items-center justify-center mx-auto mb-3 shadow">
        <i class="fas fa-paw text-white text-2xl"></i>
      </div>
      <h2 class="text-2xl font-black text-mt-brown uppercase">Crear Cuenta</h2>
    </div>
    <form @submit.prevent="registro()" class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <input x-model="regForm.nombre" required placeholder="Nombre *"
               class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
        <input x-model="regForm.apellido" required placeholder="Apellido *"
               class="px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      </div>
      <input x-model="regForm.email" type="email" required placeholder="Email *"
             class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      <input x-model="regForm.telefono" placeholder="Teléfono"
             class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      <input x-model="regForm.password" type="password" required placeholder="Contraseña (mín. 6 caracteres) *"
             class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      <p x-show="regError" x-text="regError" class="text-red-500 text-sm font-bold text-center"></p>
      <button type="submit" :disabled="regLoading"
              class="w-full bg-mt-brown text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-mt-orange transition-colors disabled:opacity-60">
        <span x-show="!regLoading">Crear Cuenta</span>
        <span x-show="regLoading"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando...</span>
      </button>
    </form>
    <p class="text-center mt-4 text-sm font-bold text-slate-400">
      ¿Ya tienes cuenta?
      <button @click="page='login'" class="text-mt-brown hover:text-mt-orange font-black hover:underline ml-1">Inicia sesión</button>
    </p>
  </div>
</div>
