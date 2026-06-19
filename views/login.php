<div x-show="page==='login'" x-cloak class="fade-in max-w-md mx-auto">
  <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-mt-cream">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-mt-orange rounded-2xl flex items-center justify-center mx-auto mb-3 shadow">
        <i class="fas fa-paw text-white text-2xl"></i>
      </div>
      <h2 class="text-2xl font-black text-mt-brown uppercase">Iniciar Sesión</h2>
    </div>
    <form @submit.prevent="login()" class="space-y-4">
      <input x-model="loginForm.email" type="email" required placeholder="Email"
             class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      <input x-model="loginForm.password" type="password" required placeholder="Contraseña"
             class="w-full px-4 py-3 rounded-xl border border-mt-cream focus:outline-none focus:border-mt-orange font-bold text-sm">
      <p x-show="loginError" x-text="loginError" class="text-red-500 text-sm font-bold text-center"></p>
      <button type="submit" :disabled="loginLoading"
              class="w-full bg-mt-brown text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-mt-orange transition-colors disabled:opacity-60">
        <span x-show="!loginLoading">Entrar</span>
        <span x-show="loginLoading"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando...</span>
      </button>
    </form>
    <p class="text-center mt-4 text-sm font-bold text-slate-400">
      ¿No tienes cuenta?
      <button @click="page='registro'" class="text-mt-brown hover:text-mt-orange font-black hover:underline ml-1">Regístrate</button>
    </p>
  </div>
</div>
