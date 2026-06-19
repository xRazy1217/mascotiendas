import { GoogleGenerativeAI } from '@google/generative-ai';
import dotenv from 'dotenv';
import * as tools from './tools.js';

dotenv.config();

if (!process.env.GEMINI_API_KEY || process.env.GEMINI_API_KEY === 'tu_api_key_aqui') {
  console.warn('⚠️ ADVERTENCIA: GEMINI_API_KEY no configurada en el archivo .env');
}

const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

const SYSTEM_PROMPT = `
Eres un vendedor y asesor humano de Mascotiendas (La Serena, Chile). Hablas directamente con los clientes por WhatsApp.
Tu objetivo es ayudarles de forma cálida, cercana, eficiente y 100% natural a encontrar productos para sus mascotas, resolver dudas sobre despachos o la tienda física, y dar recomendaciones de alimentación y cuidado.

REGLAS ABSOLUTAS DE TONO HUMANO Y CONVERSIÓN COMERCIAL:
1. **Identidad**: Jamás digas que eres un chatbot, una IA, un modelo de lenguaje o un bot. Si el cliente pregunta quién eres, eres un vendedor de Mascotiendas de la sucursal de La Serena.
2. **Naturalidad**: No estructures tus respuestas de forma artificial (evita listas con números o viñetas por defecto, a menos que estés dando una lista específica de 3 o 4 opciones de productos en stock). Escribe en párrafos cortos y fluidos, como escribiría un vendedor real escribiendo en WhatsApp.
3. **Chilenismos sutiles**: Usa términos típicos de manera muy natural y respetuosa como "al tiro", "tinca", "harto", "nos queda", "regalón", "chuta", etc. Mantén un tono respetuoso pero muy cercano y amigable. **PROHIBIDO** usar modismos vulgares, groserías o palabras informales inapropiadas para la atención al cliente (por ejemplo, NUNCA digas palabras como "xuxa", "chucha", "raja", "weón", "hueón", etc.). El lenguaje debe ser natural pero 100% educado y profesional.
4. **Cierres comerciales activos**: Al final de tu respuesta, en lugar de despedirte robóticamente ("¿En qué más te ayudo?"), haz una pregunta de cierre real de ventas o servicio, por ejemplo:
   - "Me quedan poquitos sacos de ese en stock, ¿te lo dejo guardado o te acomoda que lo agendemos para despacho?"
   - "¿Te tinca si te lo programo para el bloque de la tarde?"
   - "Avísame y te lo dejo reservado al tiro."
5. **No repetir saludos**: Si ya estás conversando con el cliente y te da información de seguimiento, no vuelvas a decirle "¡Hola! 👋" ni a saludarlo de nuevo. Responde directamente a lo que te dice.
6. **Uso de WhatsApp**: Escribe de forma legible para el celular. Usa textos en negrita (*texto*) para destacar precios, marcas o nombres de productos. Usa emojis amigables de vez en cuando (🐾, 🐶, 🐱, 😊), sin saturar.
7. **Precisión**: NUNCA inventes precios, stock ni ofertas. Si te preguntan por un producto, búscalo primero usando las herramientas.
8. **Brevedad Extrema y Cero Relleno**: En WhatsApp la gente lee rápido. Sé extremadamente breve, directo y al grano. Evita explicaciones largas, disculpas, introducciones o frases de transición. Limita tu respuesta a un máximo de 1 o 2 oraciones cortas (máximo 25-35 palabras). Nunca uses listas extensas o explicaciones de por qué no hay un producto; responde directamente con lo disponible o la alternativa.
9. **Paso a Paso (Micro-compromisos)**: Si necesitas información de la mascota para darle una recomendación, haz **una sola pregunta simple a la vez** (ej: primero pregunta si es perro o gato; cuando responda, pregunta por su edad). No abrumes al cliente con múltiples preguntas en el mismo mensaje.
10. **Gatillador de Escasez y Urgencia**: Si al buscar productos ves que el stock es bajo o si deseas incentivar la venta, menciónalo sutilmente de forma natural ("nos va quedando el último saco", "me quedan poquitos en bodega").
11. **Gatillador de Alternativa de Margen (Cross-selling)**: Si un producto consultado no tiene stock (en_stock = 0), no digas simplemente "no hay". Ofrécele inmediatamente una alternativa premium equivalente que sí tengamos disponible, destacando un beneficio clave (ej: "Chuta, de Pro Plan no me queda hoy. Pero tengo Hills que es espectacular para su digestión y pelaje, y nos queda en stock. ¿Te tinca ese?").
12. **Invocación de Herramientas (Ejecución Directa)**: Cuando necesites consultar información en la base de datos o en la wiki (buscar productos, ver stock, revisar despacho u órdenes), debes ejecutar la herramienta correspondiente de inmediato en tu primera vuelta del loop, sin decir antes frases de espera, transición o relleno como "dame un segundito", "déjame revisar", "un momento" o similares. Ejecuta la herramienta de una vez, ya que el sistema enviará al usuario tu respuesta y detendrá la conversación de inmediato si no solicitas una herramienta.
13. **Prohibido Pedir Perdón o Disculparse**: NUNCA digas "perdón", "lo siento", "disculpa", "mil disculpas" o similares. Queremos proyectar eficiencia, seguridad y resolución. Si necesitas corregir algo o responder a un malentendido, hazlo de forma directa y positiva (ej: en lugar de "Disculpa la confusión, no me queda ese stock", di "De ese no me queda stock en este momento, pero te puedo ofrecer este otro. ¿Te tinca?").
14. **Anulación de Pedidos**: Si el cliente solicita anular o cancelar su pedido (ej: "cancela mi pedido", "quiero anular el pedido #43"):
    - Ejecuta inmediatamente 'cancelClientOrder' pasando su teléfono y el 'orderId' (si te lo dio).
    - Confirma la cancelación de forma muy breve y directa (ej: "Listo, tu pedido #43 ya quedó anulado.").

INTEGRACIÓN E HISTORIAL DE PEDIDOS:
- Tienes acceso a la herramienta 'getClientOrders' para consultar el historial de pedidos de un cliente. 
- El número de teléfono de WhatsApp del cliente con el que estás chateando se te entregará en el contexto. Si el cliente pregunta "¿cómo va mi pedido?", "¿cuándo llega?", "¿qué he comprado antes?" o similar, debes ejecutar 'getClientOrders' de inmediato en la primera vuelta del ciclo, sin pedirle que te dé su número (ya lo sabes por el contexto) y responderle amablemente indicando el estado de sus pedidos recientes (ej: "Tu saco de Dockennedy de $41.900 está en estado 'preparando' y se entrega hoy...").
- También debes usar 'getClientOrders' proactivamente al inicio de un flujo de checkout para ver si el cliente ya tiene una dirección registrada en el sistema y poder sugerir usarla.

REGLAS DE AGENDAMIENTO Y DESPACHO:
- **Despacho Mismo Día**: Si el pedido se realiza en horario de **09:00 a 19:59 hrs** (según la fecha y hora provista en el contexto), se agenda y se entrega el **mismo día**. Si se realiza fuera de ese rango (desde las 20:00 hasta las 08:59 hrs), se agenda automáticamente para el **día siguiente**. Calcula la fecha de despacho correspondiente de manera precisa utilizando el contexto actual.
- **Horario Continuo (09:00 a 20:00 hrs)**: No dividas ni hables de bloques fijos de "mañana" o "tarde". Los repartidores trabajan en horario continuo de **09:00 a 20:00 hrs**. El cliente puede elegir libremente el rango de horario o la hora específica en que desea recibir su pedido dentro de este horario.
- **Validación de Horario Solicitado**: Cuando el cliente pida un horario de entrega específico, **debes compararlo obligatoriamente con la hora actual del contexto**. Si el horario solicitado ya pasó (ej: pide a las 10 AM pero ya son las 11:00), dile amablemente que ese horario ya pasó para hoy y ofrécele elegir otro rango más tarde hoy (antes de las 20:00 hrs) o para el día siguiente. Nunca registres un pedido con un horario que ya haya pasado.
- **Rango Horario Personalizado**: El cliente puede elegir si desea agendar su entrega en cualquier rango u hora específica que le acomode. Ofréceles esta flexibilidad de forma natural.
- **Notas de Despacho**: Siempre que tomes datos de entrega o coordines un despacho, pídele al cliente de forma proactiva si tiene alguna **indicación especial o nota para el repartidor** (como el color de la casa, si el timbre no funciona, entregar al vecino, etc.) para registrarla en su despacho.
- **Guardado en Base de Datos (Para actualizaciones)**: Cada vez que el cliente indique una nota para el repartidor, elija un rango horario, o cuando acuerden la fecha de entrega sobre un pedido existente, debes llamar inmediatamente a la herramienta 'updateLastOrderDeliveryDetails' para registrar estos datos en su último pedido en la base de datos de manera proactiva.

REGISTRO DE NUEVOS PEDIDOS (CHECKOUT EXTREMADAMENTE RÁPIDO):
- Cuando el cliente decida concretar una compra por chat:
  1. Ejecuta inmediatamente 'getClientOrders' para verificar si tiene pedidos previos en la base de datos.
  2. Si tiene dirección registrada, haz una sola pregunta confirmando dirección, horario de entrega y cualquier nota especial todo a la vez (ej: "Veo que la última vez enviamos a Pasaje Los Aromos 456, Peñuelas. ¿Te lo mandamos allá mismo? Confírmame y también si tienes algún rango de horario o indicación especial para el chofer."). No preguntes esto por separado en varios turnos.
  3. Si no tiene pedidos anteriores o prefiere usar otra dirección, pídele todos los datos juntos en un solo mensaje conciso: Nombre completo, Dirección de despacho (con sector), rango horario y notas para el repartidor. No preguntes de uno en uno.
  4. En cuanto responda, ejecuta 'checkDeliveryZone' para validar y presenta el desglose final y el total de inmediato para su confirmación: "Listo, el total es $XX.XXX (despacho gratis). ¿Confirma para ingresarlo?".
  5. Una vez que el cliente confirme explícitamente, debes llamar imperativamente a la herramienta 'createOrder' para guardar el pedido e ítems en la base de datos de manera inmediata.
  6. Confírmale el registro entregándole el ID del pedido retornado por 'createOrder' como su número de orden oficial.
`;

const toolsConfig = [
  {
    functionDeclarations: [
      {
        name: 'searchProducts',
        description: 'Busca productos en la base de datos de Mascotiendas por nombre o descripción corta.',
        parameters: {
          type: 'OBJECT',
          properties: {
            query: { type: 'STRING', description: 'Término de búsqueda, ej: "alimento perro", "Pro Plan"' },
            limit: { type: 'INTEGER', description: 'Límite de resultados opcional (por defecto 10)' }
          },
          required: ['query']
        }
      },
      {
        name: 'getProductDetails',
        description: 'Obtiene el detalle completo de un producto y sus variantes (formatos, pesos, precios y stock).',
        parameters: {
          type: 'OBJECT',
          properties: {
            productId: { type: 'INTEGER', description: 'ID numérico del producto' }
          },
          required: ['productId']
        }
      },
      {
        name: 'listCategories',
        description: 'Obtiene el listado completo de categorías de la tienda y la cantidad de productos en ellas.',
        parameters: {
          type: 'OBJECT',
          properties: {}
        }
      },
      {
        name: 'checkDeliveryZone',
        description: 'Consulta los costos de delivery y cobertura de despacho de zonas en La Serena y Coquimbo.',
        parameters: {
          type: 'OBJECT',
          properties: {
            query: { type: 'STRING', description: 'Nombre de la zona o sector a buscar, ej: "Peñuelas", "Las Compañías"' }
          },
          required: ['query']
        }
      },
      {
        name: 'readWikiPage',
        description: 'Lee una página de políticas estáticas locales (despacho, tienda-fisica, sop_recomendaciones) desde la wiki local.',
        parameters: {
          type: 'OBJECT',
          properties: {
            pageName: { type: 'STRING', description: 'Nombre de la página de la wiki (despacho, tienda-fisica, sop_recomendaciones)' }
          },
          required: ['pageName']
        }
      },
      {
        name: 'getClientOrders',
        description: 'Obtiene los últimos pedidos e historial de compras de un cliente en base a su número de teléfono.',
        parameters: {
          type: 'OBJECT',
          properties: {
            clientPhone: { type: 'STRING', description: 'Número de teléfono del cliente a consultar, ej: "56912345678"' }
          },
          required: ['clientPhone']
        }
      },
      {
        name: 'updateLastOrderDeliveryDetails',
        description: 'Actualiza los detalles de despacho (fechaDespacho, horaDespacho, notas) del último pedido de un cliente en la base de datos.',
        parameters: {
          type: 'OBJECT',
          properties: {
            clientPhone: { type: 'STRING', description: 'Número de teléfono del cliente (remitente de WhatsApp)' },
            fechaDespacho: { type: 'STRING', description: 'Fecha de despacho en formato YYYY-MM-DD (ej: "2026-05-24")' },
            horaDespacho: { type: 'STRING', description: 'Rango u horario de despacho preferido por el cliente (ej: "14:00 - 16:00" o "en la tarde")' },
            notas: { type: 'STRING', description: 'Indicaciones especiales o notas para el repartidor (ej: "casa azul de dos pisos", "dejar con el vecino")' }
          },
          required: ['clientPhone']
        }
      },
      {
        name: 'createOrder',
        description: 'Registra un nuevo pedido y sus ítems correspondientes en la base de datos de MySQL para venta directa.',
        parameters: {
          type: 'OBJECT',
          properties: {
            clientPhone: { type: 'STRING', description: 'Número de teléfono del cliente (remitente de WhatsApp)' },
            nombreCliente: { type: 'STRING', description: 'Nombre completo del cliente' },
            emailCliente: { type: 'STRING', description: 'Email del cliente (opcional)' },
            direccion: { type: 'STRING', description: 'Dirección física de entrega (calle, número, depto, etc.)' },
            ciudad: { type: 'STRING', description: 'Ciudad del despacho (ej: "La Serena", "Coquimbo")' },
            subtotal: { type: 'INTEGER', description: 'Subtotal del costo de los productos (suma de precios * cantidades)' },
            descuento: { type: 'INTEGER', description: 'Monto de descuento aplicado (opcional)' },
            total: { type: 'INTEGER', description: 'Costo total del pedido (subtotal + costoDelivery - descuento)' },
            metodoEntrega: { type: 'STRING', description: 'Método de entrega: "delivery" o "retiro"' },
            zonaDeliveryId: { type: 'INTEGER', description: 'ID de la zona de delivery (de checkDeliveryZone)' },
            costoDelivery: { type: 'INTEGER', description: 'Costo del despacho según la zona' },
            fechaDespacho: { type: 'STRING', description: 'Fecha de despacho en formato YYYY-MM-DD' },
            horaDespacho: { type: 'STRING', description: 'Rango horario de despacho (ej: "14:00 - 16:00")' },
            notas: { type: 'STRING', description: 'Indicaciones especiales para el repartidor (ej: "casa azul rejas negras")' },
            items: {
              type: 'ARRAY',
              description: 'Lista de productos del pedido',
              items: {
                type: 'OBJECT',
                properties: {
                  productoId: { type: 'INTEGER', description: 'ID del producto' },
                  nombre: { type: 'STRING', description: 'Nombre del producto y variante (formato/peso)' },
                  precio: { type: 'INTEGER', description: 'Precio del producto (precio_rebajado si aplica, sino precio_normal)' },
                  cantidad: { type: 'INTEGER', description: 'Cantidad comprada' }
                },
                required: ['productoId', 'nombre', 'precio', 'cantidad']
              }
            }
          },
          required: ['clientPhone', 'nombreCliente', 'direccion', 'subtotal', 'total', 'items']
        }
      },
      {
        name: 'cancelClientOrder',
        description: 'Anula un pedido específico (por su ID de pedido) o el último pedido pendiente de un cliente.',
        parameters: {
          type: 'OBJECT',
          properties: {
            clientPhone: { type: 'STRING', description: 'Número de teléfono del cliente (remitente de WhatsApp)' },
            orderId: { type: 'INTEGER', description: 'ID numérico del pedido opcional a anular' }
          },
          required: ['clientPhone']
        }
      }
    ]
  }
];

const toolsMapping = {
  searchProducts: (args) => tools.searchProducts(args.query, args.limit),
  getProductDetails: (args) => tools.getProductDetails(Number(args.productId)),
  listCategories: () => tools.listCategories(),
  checkDeliveryZone: (args) => tools.checkDeliveryZone(args.query),
  readWikiPage: (args) => tools.readWikiPage(args.pageName),
  getClientOrders: (args) => tools.getClientOrders(args.clientPhone),
  updateLastOrderDeliveryDetails: (args) => tools.updateLastOrderDeliveryDetails(args.clientPhone, args.fechaDespacho, args.horaDespacho, args.notas),
  createOrder: (args) => tools.createOrder(args),
  cancelClientOrder: (args) => tools.cancelClientOrder(args.clientPhone, args.orderId ? Number(args.orderId) : undefined)
};

/**
 * Ejecuta el Agent Loop conversacional para responder una consulta
 * @param {Array} history - Historial de mensajes en formato de Gemini [{ role: 'user'|'model', parts: [...] }]
 * @param {string} userMessage - Nuevo mensaje del cliente
 * @param {string} [clientPhone=''] - Número de teléfono del remitente (WhatsApp ID)
 */
export async function runAgent(history = [], userMessage, clientPhone = '') {
  // Obtener fecha y hora actual en Chile (America/Santiago)
  const now = new Date();
  const options = { 
    timeZone: 'America/Santiago', 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit', 
    hour: '2-digit', 
    minute: '2-digit', 
    second: '2-digit', 
    hour12: false 
  };
  const formatter = new Intl.DateTimeFormat('es-CL', options);
  const parts = formatter.formatToParts(now);
  const partMap = {};
  parts.forEach(p => partMap[p.type] = p.value);
  const chileDateTime = `${partMap.year}-${partMap.month}-${partMap.day} ${partMap.hour}:${partMap.minute}:${partMap.second}`;

  // Obtener nombre del día de la semana en Chile
  const weekdayOptions = { timeZone: 'America/Santiago', weekday: 'long' };
  const weekdayFormatter = new Intl.DateTimeFormat('es-CL', weekdayOptions);
  const chileDayName = weekdayFormatter.format(now);

  // Inyectar contexto de teléfono, día, fecha y hora local actual
  let contextStr = `\n\n[CONTEXTO ACTUAL:`;
  if (clientPhone) {
    contextStr += ` El número de teléfono del cliente es "${clientPhone}".`;
  }
  contextStr += ` La fecha y hora actual en Chile (America/Santiago) es "${chileDayName} ${chileDateTime}"].`;

  const systemInstruction = `${SYSTEM_PROMPT}${contextStr}`;

  // Inicializar modelo
  const model = genAI.getGenerativeModel({
    model: 'gemini-2.5-flash', // Usamos gemini-2.5-flash que es el modelo de producción vigente en 2026
    systemInstruction: systemInstruction,
    tools: toolsConfig
  });

  // Estructurar historial para la llamada
  const messages = [...history];
  
  // Agregar mensaje del usuario
  messages.push({
    role: 'user',
    parts: [{ text: userMessage }]
  });

  let loopCount = 0;
  const maxLoops = 6; // Límite para evitar loops infinitos de herramientas

  while (loopCount < maxLoops) {
    loopCount++;
    console.log(`[Agent Loop] Vuelta ${loopCount}...`);

    // Llamar a Gemini
    const result = await model.generateContent({ contents: messages });
    const response = result.response;
    const candidates = response.candidates;

    if (!candidates || candidates.length === 0) {
      throw new Error('No se recibió respuesta del modelo.');
    }

    const candidate = candidates[0];
    const content = candidate.content;
    
    // Verificar si el modelo solicita llamadas a herramientas (functionCalls)
    const parts = content?.parts || [];
    const functionCalls = parts.filter(p => p.functionCall);

    if (functionCalls.length > 0) {
      // Registrar el pensamiento del modelo (su llamada a función) en el historial
      messages.push({
        role: 'model',
        parts: parts
      });

      const functionResponseParts = [];

      for (const call of functionCalls) {
        const { name, args } = call.functionCall;
        console.log(`[Agent Loop] Ejecutando herramienta '${name}' con argumentos:`, args);

        let toolResult;
        try {
          if (toolsMapping[name]) {
            toolResult = await toolsMapping[name](args);
          } else {
            toolResult = { error: `Herramienta '${name}' no encontrada.` };
          }
        } catch (error) {
          console.error(`Error al ejecutar '${name}':`, error);
          toolResult = { error: error.message };
        }

        functionResponseParts.push({
          functionResponse: {
            name: name,
            response: { result: toolResult }
          }
        });
      }

      // Agregar respuestas de las herramientas al historial
      // Nota: Gemini 2.5 requiere rol 'tool' (no 'function') para las respuestas de herramientas
      messages.push({
        role: 'tool',
        parts: functionResponseParts
      });

      // Continuar el loop para que el modelo procese las respuestas de las herramientas
      continue;
    }

    // Si no hay más llamadas a funciones, esta es la respuesta final del agente
    const finalAnswer = parts.map(p => p.text || '').filter(t => t).join('\n');
    
    // Agregar la respuesta final del modelo al historial para mantener el contexto conversacional
    messages.push({
      role: 'model',
      parts: [{ text: finalAnswer }]
    });
    
    // Retornar la respuesta final y el historial actualizado
    return {
      answer: finalAnswer,
      history: messages
    };
  }

  throw new Error('El agente superó el número máximo de ciclos de herramientas sin llegar a una respuesta.');
}

/**
 * Transcribe un archivo de audio en base64 a texto utilizando Gemini 2.5 Flash.
 * @param {string} base64Data - Datos del audio codificados en base64
 * @param {string} mimeType - Tipo de medio del audio (ej: 'audio/ogg; codecs=opus')
 * @returns {Promise<string>} Texto transcrito
 */
export async function transcribeAudio(base64Data, mimeType) {
  try {
    const model = genAI.getGenerativeModel({
      model: 'gemini-2.5-flash',
      safetySettings: [
        {
          category: 'HARM_CATEGORY_HARASSMENT',
          threshold: 'BLOCK_NONE'
        },
        {
          category: 'HARM_CATEGORY_HATE_SPEECH',
          threshold: 'BLOCK_NONE'
        },
        {
          category: 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
          threshold: 'BLOCK_NONE'
        },
        {
          category: 'HARM_CATEGORY_DANGEROUS_CONTENT',
          threshold: 'BLOCK_NONE'
        }
      ]
    });
    const result = await model.generateContent([
      {
        inlineData: {
          data: base64Data,
          mimeType: mimeType
        }
      },
      'Transcribe este audio a texto en español, de forma exacta y literal, sin corregir errores ni agregar explicaciones o comentarios. Si el audio no contiene voz o es inaudible, responde únicamente con la palabra "[vacío]".'
    ]);
    return result.response.text().trim();
  } catch (error) {
    console.error('Error al transcribir audio con Gemini:', error);
    throw error;
  }
}

