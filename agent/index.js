import pkg from 'whatsapp-web.js';
const { Client, LocalAuth } = pkg;
import qrcode from 'qrcode-terminal';
import dotenv from 'dotenv';
import { runAgent, transcribeAudio } from './agent.js';
import { orderEvents } from './events.js';
import { buildDailyReport } from './reports.js';
import { getConfig, setConfig } from './config-store.js';
import { updateOrderStatus, listActionableOrders, formatActionableOrders, getOrderSummary, customerStatusMessage, ESTADOS_VALIDOS } from './orders-admin.js';
import { recipientsForEvent, listRoleConfig, setRoleNumber, resolveRole, ROLES } from './roles.js';
import { canRunCommand, canSetEstado, isAffirmation, isNegation, parseStaffIntent } from './staff.js';
import pool from './db.js';

// Envía un mensaje a todos los destinatarios que correspondan a un tipo de evento (ruteo por rol).
async function notifyRecipients(eventType, messageText) {
  const jids = await recipientsForEvent(eventType);
  for (const jid of jids) {
    try {
      await client.sendMessage(jid, messageText);
    } catch (e) {
      console.error(`[Notif] Error enviando '${eventType}' a ${jid}:`, e.message);
    }
  }
  return jids;
}

// Texto de ayuda de comandos adaptado al rol que pregunta
function staffHelp(role) {
  const lines = ['🛠️ *Comandos disponibles*', ''];
  if (canRunCommand(role, '!resumen')) lines.push('*!resumen* — ventas del día');
  if (canRunCommand(role, '!pendientes')) lines.push('*!pendientes* — pedidos por gestionar');
  if (canRunCommand(role, '!estado')) lines.push('*!estado <id> <estado>* — cambiar estado de un pedido');
  if (canRunCommand(role, '!roles')) lines.push('*!roles* — ver números por rol');
  if (canRunCommand(role, '!setrol')) lines.push('*!setrol <rol> <numero>* — configurar un rol');
  const permitidos = ESTADOS_VALIDOS.filter(e => canSetEstado(role, e));
  lines.push('', `También puedes escribir natural, ej: _"salí con el 67"_ y te pido confirmación.`);
  if (permitidos.length) lines.push(`Estados que puedes fijar: ${permitidos.join(' / ')}`);
  return lines.join('\n');
}

// Avisa al CLIENTE de un cambio de estado, verificando antes que el número esté en WhatsApp.
// Devuelve { sent: boolean, reason?: string } para informar al staff.
async function notifyCustomer(order, estado) {
  const text = customerStatusMessage(order, estado);
  if (!text) return { sent: false, reason: 'estado sin aviso' };
  const digits = String(order && order.telefono || '').replace(/\D/g, '');
  if (digits.length < 9) return { sent: false, reason: 'sin teléfono válido' };
  let numId = null;
  try {
    numId = await client.getNumberId(digits);
  } catch (e) {
    return { sent: false, reason: 'error verificando WhatsApp' };
  }
  if (!numId) return { sent: false, reason: 'el número no está en WhatsApp' };
  try {
    await client.sendMessage(numId._serialized, text);
    return { sent: true };
  } catch (e) {
    return { sent: false, reason: 'error al enviar' };
  }
}

// Aplica un cambio de estado y dispara las cascadas correspondientes
async function applyStatusChange(message, orderId, estado, role) {
  const res = await updateOrderStatus(orderId, estado);
  if (!res.success) {
    await message.reply(res.message);
    return res;
  }
  console.log(`[Staff:${role}] ${res.message.replace(/\*|_/g, '')}`);

  let extra = '';
  // Necesitamos el detalle del pedido para las cascadas
  let o = null;
  if (estado === 'pagado' || ['enviado', 'entregado', 'cancelado'].includes(estado)) {
    try { o = await getOrderSummary(res.orderId); } catch (_) {}
  }

  // Cascada 1: al confirmar el pago (transferencia), avisar a despacho que el pedido quedó listo
  if (estado === 'pagado' && o) {
    try {
      const f = o.fecha_despacho
        ? (o.fecha_despacho instanceof Date ? o.fecha_despacho.toISOString().slice(0, 10) : String(o.fecha_despacho).slice(0, 10))
        : 'sin agendar';
      const aviso = `📦 *PEDIDO LISTO PARA DESPACHAR (#${o.id})*\n\n👤 ${o.nombre_cliente}\n📍 ${o.direccion}, ${o.ciudad}\n📅 ${f} ${o.hora_despacho || ''}\n📝 ${o.notas || 'Sin notas'}\n🛒 ${o.productos || ''}`;
      await notifyRecipients('order_ready_for_dispatch', aviso);
      extra += '\n📦 Despacho avisado.';
      console.log(`[Staff] Pedido #${o.id} avisado a despacho (listo para despachar).`);
    } catch (e) {
      console.error('[Staff] Error avisando a despacho:', e);
    }
  }

  // Cascada 2: avisar al CLIENTE en enviado / entregado / cancelado
  if (['enviado', 'entregado', 'cancelado'].includes(estado) && o) {
    const r = await notifyCustomer(o, estado);
    extra += r.sent ? '\n📲 Cliente notificado.' : `\n⚠️ Cliente no notificado (${r.reason}).`;
  }

  await message.reply(res.message + extra);
  return res;
}

// Atiende a un número de staff: comandos explícitos o lenguaje natural con confirmación
async function handleStaffMessage(message, chatId, clientPhone, role, body) {
  try {
    // 1) Comandos explícitos (!...)
    if (body.startsWith('!')) {
      const parts = body.trim().split(/\s+/);
      const cmd = parts[0].toLowerCase();
      if (!canRunCommand(role, cmd)) {
        await message.reply(`🔒 Tu rol (*${role}*) no puede usar *${cmd}*.`);
        return;
      }
      if (cmd === '!resumen' || cmd === '!reporte') {
        await message.reply(await buildDailyReport());
      } else if (cmd === '!pendientes') {
        await message.reply(formatActionableOrders(await listActionableOrders(15)));
      } else if (cmd === '!estado') {
        if (parts.length < 3) {
          await message.reply(`Uso: *!estado <id> <nuevo>*\nEstados: ${ESTADOS_VALIDOS.join(' / ')}`);
        } else if (!canSetEstado(role, parts[2])) {
          await message.reply(`🔒 Tu rol (*${role}*) no puede marcar *${parts[2].toLowerCase()}*.`);
        } else {
          await applyStatusChange(message, parts[1], parts[2].toLowerCase(), role);
        }
      } else if (cmd === '!roles') {
        const cfg = await listRoleConfig();
        const lineas = ROLES.map(r => `*${r}:* ${cfg[r] ? '+' + cfg[r] : '_(sin configurar)_'}`).join('\n');
        await message.reply(`👥 *Números por rol*\n\n${lineas}\n\nCambiar: *!setrol <rol> <numero>*`);
      } else if (cmd === '!setrol') {
        if (parts.length < 3) {
          await message.reply(`Uso: *!setrol <rol> <numero>*\nRoles: ${ROLES.join(' / ')}`);
        } else {
          const r = await setRoleNumber(parts[1].toLowerCase(), parts[2]);
          await message.reply(r.message);
          if (r.success) console.log(`[Admin] Rol ${r.role} -> +${r.numero}`);
        }
      } else {
        await message.reply(staffHelp(role));
      }
      return;
    }

    // 2) ¿Hay una acción pendiente de confirmación por lenguaje natural?
    const pend = pendingStaffActions.get(chatId);
    if (pend && Date.now() < pend.expiresAt) {
      if (isAffirmation(body)) {
        pendingStaffActions.delete(chatId);
        await applyStatusChange(message, pend.orderId, pend.estado, role);
        return;
      }
      if (isNegation(body)) {
        pendingStaffActions.delete(chatId);
        await message.reply('Listo, no apliqué ningún cambio. 👍');
        return;
      }
      // si no fue sí/no, seguimos e intentamos reinterpretar el mensaje
    }

    // 3) Interpretar la intención en lenguaje natural
    const intent = parseStaffIntent(body);
    if (intent.action === 'set_status') {
      if (!canSetEstado(role, intent.estado)) {
        await message.reply(`🔒 Tu rol (*${role}*) no puede marcar *${intent.estado}*.`);
        return;
      }
      pendingStaffActions.set(chatId, { orderId: intent.orderId, estado: intent.estado, expiresAt: Date.now() + STAFF_CONFIRM_TTL_MS });
      await message.reply(`¿Marco el pedido *#${intent.orderId}* como *${intent.estado}*? Responde *sí* para confirmar.`);
    } else if (intent.action === 'need_id') {
      await message.reply(`¿Para qué número de pedido? Dime el ID, ej: *!estado 67 ${intent.estado}*.`);
    } else if (intent.action === 'list') {
      await message.reply(formatActionableOrders(await listActionableOrders(15)));
    } else if (intent.action === 'summary') {
      if (canRunCommand(role, '!resumen')) await message.reply(await buildDailyReport());
      else await message.reply(`🔒 Tu rol (*${role}*) no puede ver el resumen.`);
    } else {
      await message.reply(`No te entendí. Usa *!estado <id> <estado>* o dime algo como _"salí con el 67"_. Escribe *!ayuda* para ver opciones.`);
    }
  } catch (e) {
    console.error(`[Staff:${role}] Error procesando mensaje:`, e);
    try { await message.reply('⚠️ Ocurrió un error procesando tu mensaje. Revisa el log del bot.'); } catch (_) {}
  }
}

dotenv.config();

let landingBypassInterval = null;

// Helper para obtener el JID del administrador desde la base de datos
async function getAdminJid() {
  try {
    const [rows] = await pool.execute("SELECT valor FROM configuraciones WHERE clave = 'admin_whatsapp_number'");
    if (rows.length > 0 && rows[0].valor) {
      const cleanNum = rows[0].valor.replace(/\D/g, '');
      if (cleanNum) return `${cleanNum}@c.us`;
    }
  } catch (err) {
    console.error('Error al consultar admin_whatsapp_number en DB:', err);
  }
  return '56920571475@c.us'; // Fallback por defecto
}

// Helper para obtener el número del bot formateado para responder en errores
async function getBotPhoneFormatted() {
  try {
    const [rows] = await pool.execute("SELECT valor FROM configuraciones WHERE clave = 'bot_whatsapp_number'");
    if (rows.length > 0 && rows[0].valor) {
      const cleanNum = rows[0].valor.replace(/\D/g, '');
      if (cleanNum) {
        if (cleanNum.startsWith('569') && cleanNum.length === 11) {
          return `+56 9 ${cleanNum.slice(3, 7)} ${cleanNum.slice(7)}`;
        }
        return `+${cleanNum}`;
      }
    }
  } catch (err) {
    console.error('Error al consultar bot_whatsapp_number en DB:', err);
  }
  return '+56 9 5379 3135'; // Fallback por defecto
}

// Helper para saber si el bot debe responder únicamente a contactos no guardados en la agenda
async function getOnlyRespondToUnknown() {
  try {
    const [rows] = await pool.execute("SELECT valor FROM configuraciones WHERE clave = 'bot_only_respond_to_unknown'");
    if (rows.length > 0) {
      return rows[0].valor === '1';
    }
  } catch (err) {
    console.error('Error al consultar bot_only_respond_to_unknown en DB:', err);
  }
  return true; // Fallback seguro para evitar spam a contactos personales si hay error
}

// Helper para obtener cuántas horas se silencia el bot tras derivar un chat a un humano (default 3)
async function getHandoffPauseHours() {
  try {
    const [rows] = await pool.execute("SELECT valor FROM configuraciones WHERE clave = 'bot_handoff_pause_hours'");
    if (rows.length > 0 && rows[0].valor) {
      const h = parseInt(rows[0].valor, 10);
      if (Number.isFinite(h) && h > 0) return h;
    }
  } catch (err) {
    console.error('Error al consultar bot_handoff_pause_hours:', err);
  }
  return 3;
}


// Mapa en memoria para almacenar el historial de chats de cada contacto
// Clave: chatId (remitente), Valor: Array de mensajes en formato de Gemini [{ role, parts }]
const chatHistories = new Map();

// Mapa en memoria para controlar la velocidad de respuestas y evitar bucles eternos de bots
// Clave: chatId, Valor: { timestamps: Array<number>, isPaused: boolean, pausedUntil: number }
const rateLimits = new Map();

// Última actividad por chat (ms) para purgar conversaciones inactivas y no fugar memoria
// en un proceso 24/7. Clave: chatId, Valor: timestamp en ms.
const chatLastSeen = new Map();

// Serialización por chat: evita condiciones de carrera cuando llegan varios mensajes
// casi simultáneos del mismo remitente sobre el mismo array de historial.
const chatLocks = new Map();

// Chats derivados a un ejecutivo humano: el bot se mantiene en silencio hasta este timestamp (ms)
// para que la persona tome el control sin interferencia. Clave: chatId, Valor: until (ms).
const escalatedChats = new Map();

// Tiempo de inactividad tras el cual se descarta el estado en memoria de un chat (6 horas)
const CHAT_IDLE_TTL_MS = 6 * 60 * 60 * 1000;

// Acciones de staff (cambios de estado por lenguaje natural) pendientes de confirmar
// Clave: chatId, Valor: { orderId, estado, expiresAt }
const pendingStaffActions = new Map();
const STAFF_CONFIRM_TTL_MS = 5 * 60 * 1000;

// Límite de mensajes guardados en el historial para evitar saturar el contexto de la IA
const MAX_HISTORY_LENGTH = 20;

// Registrar la hora de encendido (en segundos UNIX) para ignorar mensajes antiguos en lote
const startupTime = Math.floor(Date.now() / 1000);

// ── Monitoreo de salud / uptime del bot ──
const HEARTBEAT_INTERVAL_MS = 60 * 1000;   // latido cada minuto
const DOWNTIME_ALERT_MINUTES = 5;          // umbral para considerar que hubo una caída real
let heartbeatInterval = null;
let reconnecting = false;

// Latido periódico: deja constancia en la BD de que el bot sigue vivo
function startHeartbeat() {
  if (heartbeatInterval) return;
  heartbeatInterval = setInterval(() => {
    setConfig('bot_last_heartbeat', Date.now());
  }, HEARTBEAT_INTERVAL_MS);
}

// Reconexión automática con backoff tras una desconexión
function attemptReconnect(attempt = 1) {
  if (reconnecting) return;
  reconnecting = true;
  const delayMs = Math.min(attempt * 15000, 120000); // hasta 2 minutos
  console.log(`[Salud] Reintentando conexión en ${delayMs / 1000}s (intento ${attempt})...`);
  setTimeout(async () => {
    try {
      await client.initialize();
      reconnecting = false;
      console.log('[Salud] Reinicialización solicitada con éxito.');
    } catch (e) {
      reconnecting = false;
      console.error(`[Salud] Falló el reintento ${attempt}:`, e.message);
      attemptReconnect(attempt + 1);
    }
  }, delayMs);
}

console.log('🤖 Iniciando Mascotiendas Bot...');

// Configurar cliente de WhatsApp con persistencia de sesión local y uso del ejecutable local de Chrome
const client = new Client({
  authStrategy: new LocalAuth({
    dataPath: './.wwebjs_auth' // Guarda las credenciales de sesión en esta carpeta
  }),
  puppeteer: {
    // Chrome 148 del sistema — mismo que creó el perfil de sesión en .wwebjs_auth
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    timeout: 0,
    protocolTimeout: 120000,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-accelerated-2d-canvas',
      '--no-first-run',
      '--no-zygote',
      '--disable-gpu',
      '--disable-extensions',
      '--disable-background-networking',
      '--disable-sync',
      '--disable-translate',
      '--hide-scrollbars',
      '--metrics-recording-only',
      '--mute-audio',
      '--safebrowsing-disable-auto-update',
      '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'
    ]
  }
});

// Mostrar progreso de carga
client.on('loading_screen', (percent, message) => {
  console.log(`⏳ Cargando WhatsApp Web: ${percent}% | Detalle: ${message}`);
});

// Evento cuando se autentica con éxito
client.on('authenticated', () => {
  console.log('🔑 Autenticación exitosa en WhatsApp.');
});

// Evento si falla la autenticación
client.on('auth_failure', async (msg) => {
  console.error('❌ Error de autenticación:', msg);
  // Persistir el estado: una falla de auth suele requerir re-escanear el QR (no se puede avisar por WhatsApp)
  await setConfig('bot_status', 'auth_failure');
  await setConfig('bot_auth_failure_at', Date.now());
});

// Mostrar código QR en la terminal para escanear
client.on('qr', (qr) => {
  console.log('\n📲 ESCANEA EL CÓDIGO QR CON TU WHATSAPP PARA INICIAR SESIÓN:\n');
  qrcode.generate(qr, { small: true });
});

// Confirmación de sesión iniciada con éxito
client.on('ready', async () => {
  console.log('\n✅ ¡Mascotiendas Bot está conectado y listo para recibir mensajes!');
  reconnecting = false;
  if (landingBypassInterval) {
    clearInterval(landingBypassInterval);
    landingBypassInterval = null;
    console.log('[Puppeteer] Bot listo. Intervalo de bypass de landing page detenido.');
  }

  // Detectar si el bot estuvo caído comparando con el último latido registrado
  try {
    const last = await getConfig('bot_last_heartbeat');
    if (last) {
      const gapMin = Math.round((Date.now() - Number(last)) / 60000);
      if (gapMin >= DOWNTIME_ALERT_MINUTES) {
        const adminJid = await getAdminJid();
        await client.sendMessage(
          adminJid,
          `⚠️ *Alerta de Bot Mascotiendas*\n\nEstuve sin conexión aproximadamente *${gapMin} min* y acabo de reconectarme. Revisa si quedaron mensajes sin responder durante ese período.`
        );
        console.warn(`[Salud] Bot reconectado tras ~${gapMin} min de caída. Administrador notificado.`);
      }
    }
  } catch (e) {
    console.error('[Salud] Error en el chequeo de downtime al reconectar:', e);
  }

  await setConfig('bot_status', 'online');
  startHeartbeat();
});

// Diagnóstico de creación de mensajes
client.on('message_create', (msg) => {
  console.log(`[message_create] De: ${msg.from} | De Mí: ${msg.id.fromMe} | Texto: "${msg.body}"`);
});

// Escuchar mensajes entrantes — se serializan por chat para evitar carreras sobre el historial
client.on('message', (message) => {
  const chatId = message.from;
  const prev = chatLocks.get(chatId) || Promise.resolve();
  const next = prev.then(() => handleMessage(message)).catch(err => {
    console.error('❌ Error no controlado en handleMessage:', err);
  });
  chatLocks.set(chatId, next);
  // Liberar la referencia del lock cuando esta cadena termina y no se encoló otra encima
  next.finally(() => { if (chatLocks.get(chatId) === next) chatLocks.delete(chatId); });
});

async function handleMessage(message) {
  try {
    const age = Math.floor(Date.now() / 1000) - message.timestamp;
    // Ignorar mensajes antiguos (recibidos hace más de 10 minutos)
    const maxAgeSeconds = 10 * 60; // 10 minutos
    if (age > maxAgeSeconds) {
      console.log(`[Mensaje Ignorado] De: ${message.from} | Razón: Antiguo (${age}s de antigüedad)`);
      return;
    }

    // Ignorar mensajes que fueron enviados antes de encender el bot (mensajes acumulados offline)
    if (message.timestamp < startupTime) {
      console.log(`[Mensaje Ignorado] De: ${message.from} | Razón: Enviado antes del encendido del bot (${message.timestamp} < ${startupTime})`);
      return;
    }

    const chat = await message.getChat();

    const chatId = message.from;

    // Marcar actividad para la purga periódica de estado en memoria
    chatLastSeen.set(chatId, Date.now());

    // Si el chat fue derivado a un ejecutivo humano, el bot permanece en silencio hasta que venza la pausa
    if (escalatedChats.has(chatId)) {
      if (Date.now() < escalatedChats.get(chatId)) {
        console.log(`[Handoff] Mensaje ignorado de ${chatId}: chat derivado a ejecutivo humano (bot en silencio).`);
        return;
      }
      escalatedChats.delete(chatId); // Venció la pausa; el bot vuelve a atender
      console.log(`[Handoff] Pausa de derivación finalizada para ${chatId}. El bot retoma la atención.`);
    }

    // Ignorar mensajes de grupos y difusiones de estado, solo responder en chats individuales privados
    if (chat.isGroup || chatId === 'status@broadcast' || chat.id._serialized === 'status@broadcast') {
      console.log(`[Mensaje Ignorado] De: ${chatId} | Razón: Grupo o Difusión`);
      return;
    }


    const now = Date.now();

    // Control de bucles: verificar si el bot está pausado para este contacto
    if (rateLimits.has(chatId)) {
      const limit = rateLimits.get(chatId);
      if (limit.isPaused) {
        if (now < limit.pausedUntil) {
          console.log(`[Rate Limit] Mensaje ignorado de ${chatId} (Bot pausado para evitar bucles).`);
          return;
        } else {
          limit.isPaused = false;
          limit.timestamps = [];
          console.log(`[Rate Limit] Período de pausa finalizado para ${chatId}.`);
        }
      }
    }
    if (message.hasMedia) {
      console.log(`[Media Recibido] Detectado archivo adjunto. Tipo: "${message.type}"`);
    }

    let messageBody = (message.body || '').trim();

    // Ignorar si el mensaje está vacío y no es una nota de voz/audio transcribible
    const isAudioMsg = message.hasMedia && (message.type === 'audio' || message.type === 'voice' || message.type === 'ptt');
    if (!messageBody && !isAudioMsg) {
      console.log(`[Mensaje Ignorado] De: ${chatId} | Razón: Mensaje vacío (notificación de sistema, cifrado o evento no conversacional)`);
      return;
    }

    // Si el mensaje es una nota de voz, audio o PTT (Push to Talk), transcribirlo con Gemini
    if (isAudioMsg) {
      console.log(`[Audio Recibido] Descargando nota de voz de ${chatId}...`);
      try {
        const media = await message.downloadMedia();
        if (media && media.data) {
          console.log(`[Audio Recibido] Transcribiendo con Gemini...`);
          const transcription = await transcribeAudio(media.data, media.mimetype);
          console.log(`[Audio Recibido] Transcripción obtenida: "${transcription}"`);
          
          if (transcription === '[vacío]' || !transcription) {
            console.log('[Audio Recibido] El audio está vacío o no contiene voz.');
            await message.reply('Disculpa, no logré escuchar bien tu audio. ¿Me lo podrías escribir o enviar otro, porfa? 🐾');
            return;
          }
          messageBody = transcription;
        } else {
          throw new Error('No se pudo descargar el archivo de audio.');
        }
      } catch (audioErr) {
        console.error('❌ Error al procesar el audio:', audioErr);
        await message.reply('Disculpa, tuve un problema al procesar tu nota de voz. ¿Podrías escribirme el mensaje, porfa? 🐾');
        return;
      }
    }

    console.log(`[Mensaje Recibido] De: ${message.author || message.from} | Texto: "${messageBody}"`);

    // Comando especial para reiniciar la conversación
    if (messageBody.toLowerCase() === '!reiniciar' || messageBody.toLowerCase() === '!limpiar') {
      chatHistories.delete(chatId);
      await message.reply('🔄 *Historial de conversación reiniciado.* ¿En qué puedo ayudarte hoy?');
      console.log(`[Historial Reiniciado] Para el chat: ${chatId}`);
      return;
    }

    // Inicializar historial si no existe
    if (!chatHistories.has(chatId)) {
      chatHistories.set(chatId, []);
    }

    const history = chatHistories.get(chatId);

    // Extraer número de teléfono real del cliente
    // WhatsApp Web puede usar @c.us (número real) o @lid (ID interno — NO es un teléfono).
    // Estrategia: si el chatId es @c.us usamos ese número directamente.
    // Si es @lid, intentamos obtener el número real desde contact._data o contact.id.
    let clientPhone = '';
    try {
      if (chatId.endsWith('@c.us')) {
        // Formato estándar: el número está directo en el JID
        clientPhone = chatId.replace('@c.us', '');
      } else {
        // Formato LID: intentar obtener el JID alternativo (@c.us) con el número real
        let resolvedJid = null;
        try {
          resolvedJid = await client.pupPage.evaluate((lid) => {
            try {
              const wid = window.require('WAWebWidFactory').createWid(lid);
              const alt = window.require('WAWebApiContact').getAlternateUserWid(wid);
              return alt ? alt.toString() : null;
            } catch (err) {
              return null;
            }
          }, chatId);
        } catch (evalErr) {
          console.warn('[Phone] Error al evaluar getAlternateUserWid en el navegador:', evalErr.message);
        }

        if (resolvedJid && resolvedJid.endsWith('@c.us')) {
          clientPhone = resolvedJid.replace('@c.us', '');
          console.log(`[Phone] Teléfono real resuelto desde JID alternativo (@lid -> @c.us): ${clientPhone}`);
        } else {
          // Fallback: buscar el número en los datos del contacto
          const contact = await message.getContact();
          const phoneFromData = contact?._data?.phoneNumber
            || contact?._data?.verifiedName
            || contact?.number;
          const digits = String(phoneFromData || '').replace(/\D/g, '');
          if (digits.length >= 10 && digits.length <= 13) {
            clientPhone = digits;
          } else {
            clientPhone = String(message.author || chatId).split('@')[0];
          }
        }
      }
      console.log(`[Phone] Teléfono final extraído: ${clientPhone} (chatId: ${chatId})`);
    } catch (contactErr) {
      clientPhone = chatId.split('@')[0];
      console.warn('[Phone] Error obteniendo contacto, usando fallback:', clientPhone);
    }

    // ── Flujo de STAFF (admin / ventas / despacho) ──
    // Si el remitente es un número de staff, lo atendemos en modo gestión (no como cliente).
    const senderRole = await resolveRole(clientPhone);
    if (senderRole) {
      await handleStaffMessage(message, chatId, clientPhone, senderRole, messageBody);
      return;
    }

    // Filtrar si está configurado para responder solo a chats nuevos (desconocidos)
    const onlyRespondToUnknown = await getOnlyRespondToUnknown();
    if (onlyRespondToUnknown) {
      const contact = await message.getContact();
      const adminJid = await getAdminJid();
      const adminPhone = adminJid.replace('@c.us', '');

      if (contact && contact.isMyContact && clientPhone !== adminPhone) {
        console.log(`[Mensaje Ignorado] De: ${chatId} (${contact.name || 'Sin Nombre'}) | Razón: Es contacto guardado en la agenda, no es el admin y el filtro está activo`);
        return;
      }
    }

    // Control de bucles: verificar límites de velocidad antes de responder
    if (!rateLimits.has(chatId)) {
      rateLimits.set(chatId, { timestamps: [], isPaused: false, pausedUntil: 0 });
    }
    const limit = rateLimits.get(chatId);
    
    // Limpiar timestamps que sean más antiguos de 1 minuto (60.000 ms)
    limit.timestamps = limit.timestamps.filter(t => now - t < 60000);

    // Si se han enviado 6 respuestas o más en el último minuto, asumimos un bucle infinito de chatbots
    if (limit.timestamps.length >= 6) {
      limit.isPaused = true;
      limit.pausedUntil = now + 900000; // Pausa de 15 minutos (900.000 ms) para pruebas y seguridad
      console.warn(`⚠️ [ALERTA DE BUCLE] Posible bucle de chatbots detectado con ${chatId}. Pausando respuestas por 15 minutos.`);
      await message.reply('🤖 *Control de seguridad:* He detectado respuestas automáticas demasiado rápidas en este chat. Para tu tranquilidad, he pausado temporalmente mis respuestas automáticas y he transferido este chat a un ejecutivo humano de nuestro equipo, quien te contactará en breve. ¡Muchas gracias por tu comprensión!');
      return;
    }

    // Registrar el timestamp de la respuesta que vamos a emitir
    limit.timestamps.push(now);

    // Indicar en WhatsApp que el bot está escribiendo
    await chat.sendStateTyping();

    // Ejecutar el ciclo de razonamiento (Agent Loop) con el historial, la nueva consulta y el teléfono del cliente
    const agentResponse = await runAgent(history, messageBody, clientPhone);

    console.log(`[Respuesta Preparada] Hacia: ${chatId} | Texto: "${agentResponse.answer}"`);

    // Salvaguarda final: nunca enviar un mensaje vacío a WhatsApp (message.reply('') falla o
    // manda un globo en blanco). Si el agente devolvió vacío, usamos un texto de aclaración.
    const safeAnswer = (agentResponse.answer && agentResponse.answer.trim())
      ? agentResponse.answer
      : '¿Me cuentas un poquito más para ayudarte? ¿Es para perro o gato? 🐾';

    // Enviar respuesta al cliente
    await message.reply(safeAnswer);

    // Si el agente derivó el caso a un humano: notificar a ventas con contexto y silenciar el bot
    if (agentResponse.escalation) {
      try {
        const pauseHours = await getHandoffPauseHours();
        escalatedChats.set(chatId, Date.now() + pauseHours * 60 * 60 * 1000);

        const rawPhone = String(clientPhone).replace(/\D/g, '');
        const cleanPhone = rawPhone.startsWith('56') && rawPhone.length >= 11
          ? rawPhone
          : rawPhone.length === 9 ? `56${rawPhone}` : rawPhone;
        const esc = agentResponse.escalation;

        const aviso = `🙋 *DERIVACIÓN A EJECUTIVO* 🙋

👤 *Cliente:* ${clientPhone ? '+' + cleanPhone : chatId}
📌 *Motivo:* ${esc.motivo || 'No especificado'}
📝 *Contexto:* ${esc.resumenContexto || 'Sin resumen'}

El bot quedó en silencio en este chat por ${pauseHours}h para que lo atiendas. Escríbele directamente al cliente.`;

        const jids = await notifyRecipients('order_handoff', aviso);
        console.log(`[Handoff] Chat ${chatId} derivado a humano. Bot en silencio ${pauseHours}h. Ventas notificado (${jids.length}).`);
      } catch (escErr) {
        console.error('[Handoff] Error al notificar la derivación:', escErr);
      }
    }

    // Actualizar el historial en memoria con el nuevo flujo retornado por el agente
    let updatedHistory = agentResponse.history;

    // Recortar historial si excede el límite para evitar costos excesivos de tokens
    if (updatedHistory.length > MAX_HISTORY_LENGTH) {
      // Recortar pero siempre empezar desde un turno 'user' para no romper
      // el orden requerido por Gemini: user → model → function → model...
      let sliced = updatedHistory.slice(updatedHistory.length - MAX_HISTORY_LENGTH);
      // Asegurarse de que el primer mensaje sea de rol 'user'
      const firstUserIdx = sliced.findIndex(m => m.role === 'user');
      updatedHistory = firstUserIdx > 0 ? sliced.slice(firstUserIdx) : sliced;
    }

    // Filtrar entradas corruptas (sin parts o con parts vacíos)
    updatedHistory = updatedHistory.filter(m => m && m.parts && m.parts.length > 0);

    chatHistories.set(chatId, updatedHistory);
    console.log(`[Respuesta Enviada] Hacia: ${chatId} | Historial actualizado (${updatedHistory.length} entradas)`);

  } catch (error) {
    console.error('❌ Error al procesar mensaje:', error);
    try {
      const botPhone = await getBotPhoneFormatted();
      await message.reply(`Lo siento, tuve un problema interno al procesar tu mensaje. Por favor, intenta de nuevo en unos momentos o contáctanos directamente al ${botPhone}.`);
    } catch (replyError) {
      console.error('Error al intentar enviar mensaje de error de respaldo:', replyError);
    }
  }
}

// Escuchar evento de creación de pedidos para notificar al rol de ventas
orderEvents.on('orderCreated', async (order) => {
  try {
    // Formatear los ítems en una lista legible
    const itemsList = order.items
      ? order.items.map(item => `- *${item.cantidad}x* ${item.nombre} (_$${item.precio.toLocaleString('es-CL')}_)`).join('\n')
      : 'Sin productos';

    // Normalizar el teléfono para mostrar como +56XXXXXXXXX
    const rawPhone = String(order.clientPhone).replace(/\D/g, '');
    // Si ya tiene código país (empieza por 56 y tiene 11 dígitos), lo usamos directo
    const cleanPhone = rawPhone.startsWith('56') && rawPhone.length >= 11
      ? rawPhone
      : rawPhone.length === 9
        ? `56${rawPhone}` // número local chileno sin prefijo
        : rawPhone;

    const notificationMessage = `🔔 *NUEVO PEDIDO REGISTRADO (#${order.orderId})* 🔔

👤 *Cliente:* ${order.nombreCliente}
📱 *Teléfono:* +${cleanPhone}
📍 *Dirección:* ${order.direccion}, ${order.ciudad}
🚚 *Método de entrega:* ${order.metodoEntrega}
💵 *Costo de envío:* $${(order.costoDelivery || 0).toLocaleString('es-CL')}
📅 *Fecha de despacho:* ${order.fechaDespacho || 'No especificada'}
⏰ *Horario de despacho:* ${order.horaDespacho || 'No especificado'}
📝 *Notas para el repartidor:* ${order.notas || 'Ninguna'}

🛒 *Productos:*
${itemsList}

💰 *Subtotal:* $${(order.subtotal || 0).toLocaleString('es-CL')}
💵 *Descuento:* $${(order.descuento || 0).toLocaleString('es-CL')}
Total: *$${(order.total || 0).toLocaleString('es-CL')}*`;

    const jids = await notifyRecipients('order_created', notificationMessage);
    console.log(`[Notificación] Nuevo pedido #${order.orderId} avisado a ventas (${jids.length} destinatario/s).`);
  } catch (error) {
    console.error('❌ Error al notificar el nuevo pedido:', error);
  }
});

// Escuchar evento de actualización de pedidos para notificar al rol de despacho
orderEvents.on('orderUpdated', async (update) => {
  try {
    // Normalizar el teléfono para mostrar como +56XXXXXXXXX
    const rawPhone = String(update.clientPhone).replace(/\D/g, '');
    const cleanPhone = rawPhone.startsWith('56') && rawPhone.length >= 11
      ? rawPhone
      : rawPhone.length === 9
        ? `56${rawPhone}`
        : rawPhone;

    // Helper para normalizar la fecha de despacho
    const formatDateVal = (val) => {
      if (!val) return 'No especificada';
      if (val instanceof Date) {
        const y = val.getFullYear();
        const m = String(val.getMonth() + 1).padStart(2, '0');
        const d = String(val.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
      }
      if (typeof val === 'string' && val.includes('T')) {
        return val.split('T')[0];
      }
      return String(val);
    };

    const newFecha = formatDateVal(update.newDetails.fechaDespacho);
    const oldFecha = formatDateVal(update.oldDetails.fechaDespacho);

    // Detectar qué campos cambiaron
    const changes = [];
    if (newFecha !== oldFecha) {
      changes.push(`📅 *Fecha de despacho:* ${newFecha} (_antes: ${oldFecha}_)`);
    }
    
    const newHora = update.newDetails.horaDespacho || 'No especificado';
    const oldHora = update.oldDetails.horaDespacho || 'No especificado';
    if (newHora !== oldHora) {
      changes.push(`⏰ *Horario de despacho:* ${newHora} (_antes: ${oldHora}_)`);
    }

    const newNotas = update.newDetails.notas || 'Ninguna';
    const oldNotas = update.oldDetails.notes || update.oldDetails.notas || 'Ninguna';
    if (newNotas !== oldNotas) {
      changes.push(`📝 *Notas para el repartidor:* ${newNotas} (_antes: ${oldNotas}_)`);
    }

    // Si no se detectan diferencias reales, no enviar nada
    if (changes.length === 0) return;

    const notificationMessage = `✏️ *PEDIDO MODIFICADO (#${update.orderId})* ✏️

👤 *Cliente:* ${update.nombreCliente}
📱 *Teléfono:* +${cleanPhone}

*Modificaciones realizadas:*
${changes.join('\n')}`;

    const jids = await notifyRecipients('order_updated', notificationMessage);
    console.log(`[Notificación] Actualización del pedido #${update.orderId} avisada a despacho (${jids.length} destinatario/s).`);
  } catch (error) {
    console.error('❌ Error al notificar la actualización del pedido:', error);
  }
});

// Escuchar evento de anulación de pedidos para notificar a ventas y despacho
orderEvents.on('orderCancelled', async (order) => {
  try {
    // Normalizar el teléfono para mostrar como +56XXXXXXXXX
    const rawPhone = String(order.clientPhone).replace(/\D/g, '');
    const cleanPhone = rawPhone.startsWith('56') && rawPhone.length >= 11
      ? rawPhone
      : rawPhone.length === 9
        ? `56${rawPhone}`
        : rawPhone;

    const notificationMessage = `🚨 *PEDIDO ANULADO (#${order.orderId})* 🚨

👤 *Cliente:* ${order.nombreCliente}
📱 *Teléfono:* +${cleanPhone}
💰 *Monto Total:* $${(order.total || 0).toLocaleString('es-CL')}

El cliente ha solicitado la anulación de este pedido directamente desde el chat de WhatsApp.`;

    const jids = await notifyRecipients('order_cancelled', notificationMessage);
    console.log(`[Notificación] Anulación del pedido #${order.orderId} avisada a ventas y despacho (${jids.length} destinatario/s).`);
  } catch (error) {
    console.error('❌ Error al notificar la anulación del pedido:', error);
  }
});

// Manejo de desconexión: registrar estado, detener el latido e intentar reconectar
client.on('disconnected', async (reason) => {
  console.error('⚠️ El bot se desconectó de WhatsApp:', reason);
  if (heartbeatInterval) {
    clearInterval(heartbeatInterval);
    heartbeatInterval = null;
  }
  await setConfig('bot_status', 'offline');
  await setConfig('bot_offline_since', Date.now());
  // Nota: el último 'bot_last_heartbeat' queda como marca de la caída; al volver 'ready' se calcula el downtime.
  attemptReconnect();
});

// Captura periódica de pantalla para diagnóstico visual y bypass de landing page
landingBypassInterval = setInterval(async () => {
  if (client.pupPage) {
    try {
      const info = await client.pupPage.evaluate(() => {
        const elements = Array.from(document.querySelectorAll('a, button, [role="button"], span, div'));
        const target = elements.find(el => {
          const text = el.textContent ? el.textContent.trim().replace(/\s+/g, ' ') : '';
          return text === 'Continuar en WhatsApp Web';
        });
        if (target) {
          const detail = {
            tagName: target.tagName,
            outerHTML: target.outerHTML.slice(0, 200),
            href: target.href || null,
            targetAttr: target.target || null
          };
          if (target.tagName === 'A' && target.target === '_blank') {
            target.target = '_self';
          }
          target.click();
          return detail;
        }
        return null;
      });
      if (info) {
        console.log('[Puppeteer] Encontrado y clicado el elemento exacto para continuar:', info);
      }
    } catch (err) {
      console.error('[Puppeteer] Error al intentar evadir landing page:', err);
    }
    try {
      // Captura local para diagnóstico durante la fase de carga/QR (se detiene en 'ready')
      await client.pupPage.screenshot({ path: './whatsapp-debug.png' });
    } catch (e) {
      // Ignorar si la página aún no está lista
    }
  }
}, 10000);

// Purga periódica del estado en memoria de chats inactivos para no fugar memoria (proceso 24/7)
setInterval(() => {
  const now = Date.now();
  let purgados = 0;
  for (const [chatId, lastSeen] of chatLastSeen.entries()) {
    if (now - lastSeen > CHAT_IDLE_TTL_MS) {
      chatHistories.delete(chatId);
      rateLimits.delete(chatId);
      chatLastSeen.delete(chatId);
      purgados++;
    }
  }
  // Limpiar derivaciones vencidas que ya no se reactivaron por un mensaje del cliente
  for (const [chatId, until] of escalatedChats.entries()) {
    if (now >= until) escalatedChats.delete(chatId);
  }
  if (purgados > 0) {
    console.log(`[Limpieza] Estado en memoria purgado de ${purgados} chat(s) inactivo(s). Activos: ${chatLastSeen.size}`);
  }
}, 30 * 60 * 1000); // cada 30 minutos

// ─────────────────────────────────────────────────────────────────────────────
// Resumen diario automático de ventas al administrador
// ─────────────────────────────────────────────────────────────────────────────
let lastReportSentDate = null;

// Hora objetivo de envío (config 'admin_daily_report_time', formato HH:MM, default 21:00)
async function getDailyReportTime() {
  try {
    const [rows] = await pool.execute("SELECT valor FROM configuraciones WHERE clave = 'admin_daily_report_time'");
    if (rows.length > 0 && rows[0].valor && /^\d{1,2}:\d{2}$/.test(rows[0].valor.trim())) {
      return rows[0].valor.trim().padStart(5, '0');
    }
  } catch (err) {
    console.error('Error al consultar admin_daily_report_time:', err);
  }
  return '21:00';
}

// Fecha y hora actuales en Chile como { date: 'YYYY-MM-DD', hm: 'HH:MM' }
function chileNowParts() {
  const fmt = new Intl.DateTimeFormat('es-CL', {
    timeZone: 'America/Santiago', year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit', hour12: false
  });
  const p = {};
  fmt.formatToParts(new Date()).forEach(x => p[x.type] = x.value);
  return { date: `${p.year}-${p.month}-${p.day}`, hm: `${p.hour}:${p.minute}` };
}

// Cargar la última fecha enviada desde la BD para no duplicar el resumen tras un reinicio
(async () => {
  try {
    const [rows] = await pool.execute("SELECT valor FROM configuraciones WHERE clave = 'admin_daily_report_last'");
    if (rows.length > 0 && rows[0].valor) lastReportSentDate = rows[0].valor.trim();
  } catch (err) {
    console.error('Error al cargar admin_daily_report_last:', err);
  }
})();

// Revisar cada minuto si corresponde enviar el resumen del día
setInterval(async () => {
  try {
    const { date, hm } = chileNowParts();
    if (lastReportSentDate === date) return; // ya se envió hoy
    const target = await getDailyReportTime();
    if (hm >= target) {
      const adminJid = await getAdminJid();
      const report = await buildDailyReport();
      await client.sendMessage(adminJid, report);
      lastReportSentDate = date;
      await pool.execute(
        "INSERT INTO configuraciones (clave, valor) VALUES ('admin_daily_report_last', ?) ON DUPLICATE KEY UPDATE valor = ?",
        [date, date]
      );
      console.log(`[Resumen Diario] Enviado al administrador a las ${hm} (objetivo ${target}).`);
    }
  } catch (err) {
    console.error('[Resumen Diario] Error en el scheduler:', err);
  }
}, 60000);

// Iniciar conexión
client.initialize();

