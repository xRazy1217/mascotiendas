# 🤖 Mascotiendas WhatsApp Bot

Este es el bot oficial de WhatsApp para **Mascotiendas La Serena/Coquimbo**. Su objetivo principal es interactuar de manera fluida y con tono local chileno con los clientes, gestionar el stock, y permitir a los clientes registrar y actualizar detalles de despacho de sus pedidos de manera automática en la base de datos, notificando en tiempo real al administrador de la tienda.

---

## 🗺️ Arquitectura y Flujo del Sistema

El bot está construido sobre **Node.js** usando la librería `whatsapp-web.js` para controlar una sesión web de WhatsApp a través de un navegador Puppeteer (Chrome headless). Utiliza **Gemini** (a través de la API oficial de Google Gen AI) como motor de entendimiento y razonamiento, conectándose a una base de datos **MariaDB** local (WAMP).

El flujo de información es el siguiente:

```mermaid
graph TD
    User([Cliente en WhatsApp]) -->|Envía Mensaje| Index[index.js (WhatsApp Client)]
    Index -->|Extrae Teléfono & Historial| Agent[agent.js (Gemini Agent)]
    Agent -->|Razona y ejecuta herramientas| Tools[tools.js (SQL Queries)]
    Tools -->|Actualiza/Consulta| DB[(Base de Datos MariaDB)]
    Tools -->|Emite Eventos de Pedidos| Events[events.js (EventEmitter)]
    Events -->|Detecta orderCreated / orderUpdated| Index
    Index -->|Envía Alerta Formateada| Admin([Administrador +56920571475])
    Agent -->|Genera Respuesta Final| Index
    Index -->|Responde con tono chileno| User
```

---

## 📂 Estructura de Componentes

### 1. [index.js](file:///C:/wamp64/www/mascotiendas/agent/index.js) (Punto de Entrada)
- Inicializa el cliente de WhatsApp Web utilizando `LocalAuth` para mantener la sesión abierta.
- Escucha los eventos `message` y `message_create`.
- Descarga y transcribe notas de voz/audio recibidas utilizando Gemini.
- Resuelve IDs internos de WhatsApp (`@lid`) a números telefónicos reales (`@c.us`).
- Ejecuta el bucle de Gemini (`runAgent`) y envía las respuestas al usuario.
- Escucha los eventos globales `orderCreated` y `orderUpdated` (desde `events.js`) para notificar al administrador en la central (`56920571475@c.us`) con resúmenes claros que muestran los cambios (antiguos vs nuevos).

### 2. [agent.js](file:///C:/wamp64/www/mascotiendas/agent/agent.js) (Motor de Inteligencia Artificial)
- Configura e invoca el modelo de Gemini.
- Administra el prompt de sistema y las reglas de personalidad (tono chileno, amable, no vulgar, horario de atención de 09:00 a 20:00 hrs).
- Expone las funciones de `tools.js` como herramientas de función (Function Calling) de Gemini.
- Implementa el bucle de razonamiento (Agent Loop) que autoejecuta herramientas secuencialmente hasta formular una respuesta conversacional final.

### 3. [tools.js](file:///C:/wamp64/www/mascotiendas/agent/tools.js) (Herramientas de Base de Datos)
- **`searchProducts(query, limit)`**: Busca productos activos por coincidencia de palabras clave normalizadas.
- **`getProductDetails(productId)`**: Recupera información completa de variantes, stock y precios de un producto.
- **`createOrderFromChat(...)`**: Inserta nuevos pedidos y sus líneas de detalle en la base de datos, calculando subtotales y costos de delivery.
- **`updateLastOrderDeliveryDetails(clientPhone, fechaDespacho, horaDespacho, notas)`**: Busca el último pedido pendiente del cliente y actualiza sus datos de despacho. Emite el evento `orderUpdated` para alertas.

### 4. [db.js](file:///C:/wamp64/www/mascotiendas/agent/db.js) (Conexión)
- Exporta un pool de conexiones a la base de datos MariaDB (WAMP, puerto `3308`) utilizando `mysql2/promise`.

### 5. [events.js](file:///C:/wamp64/www/mascotiendas/agent/events.js) (Manejador de Eventos)
- Un `EventEmitter` compartido en memoria que sincroniza notificaciones asíncronas entre la lógica de negocio (`tools.js`) y la interfaz de WhatsApp (`index.js`).

---

## ⚙️ Filtros de Seguridad y Control de Mensajes (Anti-Spam y Anti-Loop)

El bot implementa varios filtros críticos en [index.js](file:///C:/wamp64/www/mascotiendas/agent/index.js) para asegurar que solo atienda a clientes válidos, no responda tarde y no caiga en bucles con bots externos:

### 1. Filtro de Contactos Guardados (Solo Chats Nuevos)
- **Propósito:** Evitar responder a contactos personales guardados en la agenda (familia, amigos, proveedores).
- **Cómo funciona:** Si la clave `bot_only_respond_to_unknown` en la base de datos es `'1'` (activable mediante el switch en el Panel Admin Dashboard), el bot consulta `contact.isMyContact`. Si es `true`, ignora el mensaje de manera silenciosa.
- **Excepción del Administrador:** El bot siempre permite mensajes del número administrador configurado en `admin_whatsapp_number` para permitir pruebas operativas, incluso si está guardado en la agenda.

### 2. Filtro de Mensajes Antiguos (Offline)
- **Propósito:** Impedir que el bot responda con retraso a mensajes acumulados que llegaron mientras estaba apagado.
- **Cómo funciona:** Se define la constante `startupTime` al arrancar el proceso de Node. Si `message.timestamp < startupTime`, el mensaje se ignora de forma inmediata.

### 3. Filtro de Mensajes Vacíos / Notificaciones
- **Propósito:** Impedir responder a notificaciones automáticas de WhatsApp Web (cifrado, sincronización, actualizaciones de chats) que se disparan al cargar la página con texto vacío `""`.
- **Cómo funciona:** Si el cuerpo del mensaje está vacío y no se trata de una nota de voz transcribible (`isAudioMsg` es falso), el bot lo ignora automáticamente.

### 4. Limitador de Bucles (Anti-Bot Loop)
- **Propósito:** Detener interacciones de ping-pong infinitas si el bot chatea con otro chatbot.
- **Cómo funciona:** Mantiene un registro en memoria de las respuestas enviadas. Si se superan las 6 respuestas a un mismo chat en el último minuto, el bot pausa sus respuestas para ese remitente durante 15 minutos y envía un mensaje indicándolo.

---

## 🗄️ Campos Críticos en la Base de Datos (`pedidos`)

Para la gestión de despachos, la tabla `pedidos` en la base de datos `mascotiendas` utiliza los siguientes campos clave:
- `telefono`: Almacena el número del cliente en formato internacional estándar (ej: `+56920571475`).
- `fecha_despacho`: Almacena el día de entrega (`DATE` o `DATETIME`).
- `hora_despacho`: Almacena el rango de horario preferido (ej: `12:00`, `11:00 AM`, `15:00 - 17:00`).
- `notas`: Indicaciones específicas para el repartidor (ej: *"portón negro"*, *"tocar el timbre de madera"*).
- `estado`: Estado del pedido (`pendiente`, `completado`, etc.).

---

## ⚠️ Errores Comunes y Soluciones (Saber esto evitará dolores de cabeza)

### 1. Bloqueo del Perfil de Sesión Puppeteer (`.wwebjs_auth`)
- **Error**: Al reiniciar el bot abruptamente, a veces el perfil de Chrome queda bloqueado por procesos huérfanos de Chrome. El bot se queda colgado indefinidamente.
- **Solución**: Asegurarse de cerrar todos los procesos Chrome huérfanos en Windows ejecutando el comando de PowerShell:
  ```powershell
  Get-CimInstance Win32_Process -Filter "Name = 'chrome.exe'" | Where-Object { $_.CommandLine -like "*user-data-dir=C:\wamp64*" -or $_.CommandLine -like "*noerrdialogs*" } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }
  ```

### 2. Mensajes No Recibidos tras Reinicios (Mensajes en "Limbo")
- **Error**: Si el cliente envía un mensaje cuando el bot está apagado o en proceso de carga, dicho mensaje se marca como unread en WhatsApp pero **nunca** gatillará los eventos `.on('message')` o `.on('message_create')` al encender el bot.
- **Solución**: Para pruebas o uso real, el cliente debe enviar un **mensaje nuevo** cuando el bot ya haya registrado el log de conexión lista (`Mascotiendas Bot está conectado...`).

### 3. Resolución de IDs de WhatsApp `@lid`
- **Error**: Clientes nuevos o contactos sincronizados en ciertos dispositivos a veces envían mensajes bajo el JID de ID interno (`1234567890@lid`) en lugar del JID con el número telefónico real (`569XXXXXXXX@c.us`). Si se busca en la base de datos usando el ID `@lid`, no se encontrará ningún pedido.
- **Solución**: Implementamos en `index.js` una evaluación de script dentro del navegador que consulta la API interna de WhatsApp Web:
  ```javascript
  const wid = window.require('WAWebWidFactory').createWid(lid);
  const alt = window.require('WAWebApiContact').getAlternateUserWid(wid);
  return alt ? alt.toString() : null;
  ```
  Esto devuelve el JID `@c.us` real con el número telefónico del cliente para realizar búsquedas exitosas en la base de datos.

### 4. Doble Inicialización / Disparo de Evento `ready` Duplicado
- **Error**: WhatsApp Web realiza cargas diferidas que a veces disparan múltiples cambios en el estado de sincronización (`hasSynced`). Si se evalúa inmediatamente, el evento `ready` se dispara varias veces.
- **Solución**: Modificamos el core en `Client.js` de `whatsapp-web.js` para rastrear la inicialización con un flag `syncedCalled`:
  ```javascript
  let syncedCalled = false;
  socket.on('change:hasSynced', () => {
      if (!syncedCalled) {
          syncedCalled = true;
          window.onAppStateHasSyncedEvent();
      }
  });
  ```

### 5. Apagado del Bot por Cancelación de Pasos de Agente (en Entornos Dev)
- **Error**: En herramientas de desarrollo o consolas de agentes de IA, cada vez que el usuario ingresa un mensaje de respuesta en el chat, el sistema cancela los subprocesos de la consola de comandos de PowerShell activa. Esto apaga el chatbot.
- **Solución**: Arrancar el bot como un proceso completamente desvinculado del árbol de procesos utilizando WMI (`CIM`). Esto le permite correr de forma persistente y autónoma bajo el servicio del sistema:
  ```powershell
  Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = 'cmd.exe /c ""C:\nvm4w\nodejs\node.exe" index.js > chatbot.log 2>&1"'; CurrentDirectory = 'C:\wamp64\www\mascotiendas\agent' }
  ```

---

## 🚀 Guía de Operación y Monitoreo

### Iniciar el Bot en Modo de Consola (Pruebas Locales)
Si estás en una terminal interactiva normal y quieres ver el output en tiempo real:
```bash
node index.js
```

### Iniciar el Bot en Segundo Plano Persistente (Producción/Testing Desvinculado)
Para evitar que se apague al interactuar con herramientas del desarrollador:
```powershell
Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = 'cmd.exe /c ""C:\nvm4w\nodejs\node.exe" index.js > chatbot.log 2>&1"'; CurrentDirectory = 'C:\wamp64\www\mascotiendas\agent' }
```

### Ver el Estado de Ejecución
Para saber si el bot está corriendo y cuál es su ID de proceso en Windows:
```powershell
Get-Process node -ErrorAction SilentlyContinue | Select-Object Id, ProcessName, StartTime
```

### Monitorear Logs en Tiempo Real
Para ver los mensajes recibidos, respuestas enviadas, y llamadas a base de datos en tiempo real:
```powershell
Get-Content -Path "chatbot.log" -Wait -Tail 20
```

### Detener el Bot
Busca el ID del proceso `node` y finalízalo:
```powershell
Stop-Process -Name node -Force
```
*(Nota: Asegúrate también de correr el comando de eliminación de procesos Chrome huérfanos listados en la sección de errores comunes).*
