import { GoogleGenerativeAI } from '@google/generative-ai';
import { runAgent } from './agent.js';
import dotenv from 'dotenv';

dotenv.config();

if (!process.env.GEMINI_API_KEY) {
  console.error('❌ Error: GEMINI_API_KEY no encontrada en el archivo .env');
  process.exit(1);
}

const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

const customerSystemInstruction = `
Eres un cliente chileno real llamado Guillermo Portales. Tu número de teléfono es +56949865594.
Estás chateando por WhatsApp con "Mascotiendas", una tienda de alimentos y accesorios para mascotas.

Tu objetivo principal:
Quieres comprar un saco de alimento para tu perro senior de raza mediana/grande. Buscas específicamente:
"Fit formula Senior raza mediana-grande 20 kgs"

Información de despacho para cuando te la pregunten:
- Método de entrega: Delivery (despacho a domicilio)
- Dirección: Vicuña 2574, Coquimbo
- Horario de despacho: entre las 15:00 y las 17:00 hrs
- Notas especiales para el repartidor: Ninguna

Reglas de comportamiento para sonar como un humano en WhatsApp:
1. Habla de forma muy casual y natural en español chileno (usa palabras como "hola, buenas", "tienen...", "súper", "vale", "dale", "tinca", "al tiro").
2. No uses una redacción perfecta ni formal. Escribe en minúsculas en su mayoría, puedes usar abreviaciones comunes ("q" en lugar de "que", "tb" o "tbn" en lugar de "también").
3. Responde a lo que te diga el vendedor. No te adelantes con toda tu información al principio. Hazlo paso a paso como en un chat real.
4. Si el vendedor te ofrece el producto y te indica el precio, dile que quieres comprarlo.
5. Si te pide tu dirección, dásela indicando que es de Coquimbo.
6. Si te pide un horario, dile que prefieres entre las 15:00 y las 17:00 hrs.
7. Si te pregunta por indicaciones para el repartidor, dile que ninguna.
8. Cuando te muestre el total detallado y te pida confirmar para registrar el pedido, dile claramente que sí (ej: "sí, dale", "confírmalo porfa").
9. Escribe mensajes cortos (1 o 2 líneas máximo), sin viñetas, sin listas, y NUNCA uses etiquetas como "Cliente:" o prefijos en tu respuesta. Envía solo el mensaje de texto de WhatsApp.
`;

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

async function runSimulation() {
  console.log('\n======================================================');
  console.log('🤖  INICIANDO SIMULACIÓN DE CONVERSACIÓN DE IA A IA  🤖');
  console.log('======================================================\n');

  // Inicializar el cliente AI y variables
  const customerModel = genAI.getGenerativeModel({
    model: 'gemini-2.5-flash',
    systemInstruction: customerSystemInstruction
  });
  
  const clientPhone = '56949865594'; // Teléfono a simular
  let currentMessage = 'hola buenas, tienen alimento para perro senior?';
  
  console.log(`\x1b[36m👤 [Guillermo Portales (+56 9 4986 5594)]:\x1b[0m ${currentMessage}`);

  // Historial del bot Mascotiendas (Sujeto de pruebas)
  let botHistory = [];
  
  // Historial de la conversación formateada en texto para el Cliente AI
  let dialogueLog = [];
  dialogueLog.push(`Cliente: ${currentMessage}`);

  // Ejecutar el loop por máximo 12 turnos o hasta que termine el pedido
  for (let turn = 1; turn <= 12; turn++) {
    await sleep(2500); // Pequeña pausa para simular escritura natural y lectura amigable
    
    console.log('\n\x1b[2m🤖 Mascotiendas Bot pensando/procesando herramientas...\x1b[0m');
    
    try {
      // 1. Ejecutar el agente chatbot
      const botResponse = await runAgent(botHistory, currentMessage, clientPhone);
      
      // Actualizar el historial del bot
      botHistory = botResponse.history;
      
      const botAnswer = botResponse.answer;
      console.log(`\x1b[32m🐾 [Mascotiendas Bot]:\x1b[0m ${botAnswer}`);
      dialogueLog.push(`Vendedor: ${botAnswer}`);

      // Verificar si el pedido ya fue finalizado (ej. si menciona el número de orden #)
      if (botAnswer.includes('#') && (botAnswer.toLowerCase().includes('pedido') || botAnswer.toLowerCase().includes('orden') || botAnswer.toLowerCase().includes('registrado'))) {
        console.log('\n\x1b[32m🎉 ¡Simulación completada con éxito! El pedido fue creado.\x1b[0m\n');
        break;
      }

      await sleep(2500);

      // 2. Generar respuesta del cliente AI basándose en el historial de diálogo
      const prompt = `Aquí está la conversación de WhatsApp hasta ahora:
${dialogueLog.join('\n')}

Por favor, genera únicamente tu siguiente respuesta como Cliente (Guillermo Portales). Escribe solo tu respuesta directa sin etiquetas ni explicaciones, siguiendo las instrucciones de comportamiento.`;

      const result = await customerModel.generateContent(prompt);
      currentMessage = result.response.text().trim();
      
      // Limpiar prefijos si el modelo los incluyó por error
      currentMessage = currentMessage.replace(/^(Guillermo Portales:|Cliente:|Usuario:)\s*/i, '');

      console.log(`\x1b[36m👤 [Guillermo Portales (+56 9 4986 5594)]:\x1b[0m ${currentMessage}`);
      dialogueLog.push(`Cliente: ${currentMessage}`);

    } catch (err) {
      console.error('\n❌ Ocurrió un error en la simulación:', err);
      break;
    }
  }

  console.log('======================================================');
  console.log('🏁               FIN DE LA SIMULACIÓN                 ');
  console.log('======================================================\n');
}

runSimulation();
