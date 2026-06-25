/** @type {import('tailwindcss').Config} */
// Colores hardcodeados a los defaults de la BD.
// Si se cambian los colores en configuraciones, ejecutar: npm run css
module.exports = {
  content: [
    './index.php',
    './admin/index.php',
    './views/**/*.php',
    './includes/**/*.php',
    './admin/views/**/*.php',
    './assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        'mt-brown': '#F7941D',
        'mt-orange': '#7F5234',
        'mt-cream':  '#F9F1E7',
      }
    }
  },
  plugins: [],
}
