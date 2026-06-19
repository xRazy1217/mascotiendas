import readline from 'readline';
import { runAgent } from './agent.js';
import dotenv from 'dotenv';

dotenv.config();

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

let history = [];
let clientPhone = '';

console.log('🧪 Mascotiendas Agent CLI Test Tool');
console.log('Escribe "salir" para terminar o "!reiniciar" para limpiar la memoria.\n');

// Preguntar qué teléfono simular al iniciar
rl.question('📱 Ingresa el número de teléfono del cliente a simular (ej. +56953793135 o presiona Enter para omitir): ', (phone) => {
  clientPhone = phone.trim();
  console.log(`\n💬 Simulación iniciada con el teléfono: "${clientPhone || 'No especificado'}"`);
  console.log('Comienza a chatear con el bot:\n');
  askQuestion();
});

function askQuestion() {
  rl.question('👤 Usuario: ', async (input) => {
    const trimmedInput = input.trim();
    
    if (trimmedInput.toLowerCase() === 'salir') {
      rl.close();
      process.exit(0);
    }
    
    if (trimmedInput.toLowerCase() === '!reiniciar') {
      history = [];
      console.log('🔄 Historial reiniciado.\n');
      askQuestion();
      return;
    }

    if (!trimmedInput) {
      askQuestion();
      return;
    }

    try {
      console.log('🤖 Pensando...');
      const response = await runAgent(history, trimmedInput, clientPhone);
      
      console.log(`\n🤖 Mascotiendas Bot:\n${response.answer}\n`);
      
      // Guardar el historial actualizado
      history = response.history;
    } catch (error) {
      console.error('\n❌ Error al ejecutar el agente:', error.message);
      console.log('Asegúrate de haber configurado tu GEMINI_API_KEY en el archivo .env y que la base de datos local esté corriendo en WAMP (puerto 3308).\n');
    }

    askQuestion();
  });
}
