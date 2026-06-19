import pool from './db.js';
import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';
import { orderEvents } from './events.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);


/**
 * Busca productos por nombre o descripción corta.
 * @param {string} query - Término de búsqueda
 * @param {number} [limit=10] - Límite de resultados
 */
export async function searchProducts(query, limit = 10) {
  try {
    const safeLimit = Math.max(1, parseInt(limit) || 10);
    
    // Limpiar y separar palabras de la consulta
    const words = query.trim().split(/\s+/).map(w => w.toLowerCase());
    
    // Normalizar palabras (quitar plurales básicos 's' o 'es' para español)
    const normalizedWords = words.map(w => {
      if (w.endsWith('es') && w.length > 4) return w.slice(0, -2);
      if (w.endsWith('s') && w.length > 3) return w.slice(0, -1);
      return w;
    });

    // Construir consulta SQL dinámica
    const whereConditions = [];
    const params = [];
    
    for (const word of normalizedWords) {
      if (word.length < 2) continue; // Ignorar conectores cortos
      whereConditions.push('(p.nombre LIKE ? OR p.descripcion_corta LIKE ? OR c.nombre LIKE ?)');
      params.push(`%${word}%`, `%${word}%`, `%${word}%`);
    }

    // Si no quedaron palabras válidas, usar el query original
    if (whereConditions.length === 0) {
      whereConditions.push('(p.nombre LIKE ? OR p.descripcion_corta LIKE ? OR c.nombre LIKE ?)');
      params.push(`%${query}%`, `%${query}%`, `%${query}%`);
    }

    const whereSQL = `p.activo = 1 AND (${whereConditions.join(' OR ')})`;

    const [rows] = await pool.execute(
      `SELECT DISTINCT p.id, p.nombre, p.precio_normal, p.precio_rebajado, p.en_stock, p.slug, p.tiene_variantes,
              (SELECT url FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.posicion = 0 LIMIT 1) AS imagen
       FROM productos p
       LEFT JOIN producto_categorias pc ON p.id = pc.producto_id
       LEFT JOIN categorias c ON pc.categoria_id = c.id
       WHERE ${whereSQL}
       LIMIT ${safeLimit}`,
      params
    );
    return rows;
  } catch (error) {
    console.error('Error en searchProducts:', error);
    throw new Error('No se pudieron buscar los productos.');
  }
}

/**
 * Obtiene el detalle completo de un producto y sus variantes
 * @param {number} productId - ID del producto
 */
export async function getProductDetails(productId) {
  try {
    // 1. Obtener datos del producto
    const [productRows] = await pool.execute(
      `SELECT * FROM productos WHERE id = ? AND activo = 1`,
      [productId]
    );
    
    if (productRows.length === 0) {
      return null;
    }
    
    const product = productRows[0];

    // 2. Obtener imágenes
    const [images] = await pool.execute(
      `SELECT url, posicion FROM producto_imagenes WHERE producto_id = ? ORDER BY posicion`,
      [productId]
    );
    product.imagenes = images;

    // 3. Obtener categorías
    const [categories] = await pool.execute(
      `SELECT c.nombre, c.slug 
       FROM producto_categorias pc 
       JOIN categorias c ON pc.categoria_id = c.id 
       WHERE pc.producto_id = ?`,
      [productId]
    );
    product.categorias = categories;

    // 4. Obtener variantes si las tiene
    if (product.tiene_variantes) {
      const [variants] = await pool.execute(
        `SELECT id, sku, precio_normal, precio_rebajado, stock, en_stock, imagen_url 
         FROM producto_variantes 
         WHERE producto_id = ? AND activo = 1`,
        [productId]
      );

      // Cargar atributos para cada variante
      for (const variant of variants) {
        const [attributes] = await pool.execute(
          `SELECT a.nombre AS atributo, av.valor 
           FROM variante_atributos va
           JOIN atributo_valores av ON va.atributo_valor_id = av.id
           JOIN atributos a ON av.atributo_id = a.id
           WHERE va.variante_id = ?`,
          [variant.id]
        );
        variant.atributos = attributes;
      }
      product.variantes = variants;
    } else {
      product.variantes = [];
    }

    return product;
  } catch (error) {
    console.error('Error en getProductDetails:', error);
    throw new Error('No se pudo obtener el detalle del producto.');
  }
}

/**
 * Lista todas las categorías activas y la cantidad de productos en cada una
 */
export async function listCategories() {
  try {
    const [rows] = await pool.execute(
      `SELECT c.id, c.nombre, c.slug, COUNT(pc.producto_id) as total_productos 
       FROM categorias c 
       LEFT JOIN producto_categorias pc ON c.id = pc.categoria_id 
       GROUP BY c.id 
       ORDER BY c.nombre`
    );
    return rows;
  } catch (error) {
    console.error('Error en listCategories:', error);
    throw new Error('No se pudieron listar las categorías.');
  }
}

/**
 * Consulta la cobertura y costos de delivery locales
 * @param {string} query - Nombre de la zona a buscar (ej: "Coquimbo")
 */
export async function checkDeliveryZone(query) {
  try {
    const [rows] = await pool.execute(
      `SELECT id, nombre, descripcion, costo 
       FROM zonas_delivery 
       WHERE activo = 1 AND (nombre LIKE ? OR descripcion LIKE ?)`,
      [`%${query}%`, `%${query}%`]
    );
    return rows;
  } catch (error) {
    console.error('Error en checkDeliveryZone:', error);
    throw new Error('No se pudo consultar la zona de delivery.');
  }
}

/**
 * Lee una página de la wiki local (Markdown) sobre despacho, tienda-fisica, etc.
 * @param {string} pageName - Nombre del archivo sin extensión (ej: 'despacho', 'tienda-fisica')
 */
export async function readWikiPage(pageName) {
  try {
    // Validar nombre para evitar path traversal
    const safePageName = pageName.replace(/[^a-zA-Z0-9_-]/g, '');
    const filePath = path.join(__dirname, 'wiki', `${safePageName}.md`);
    const content = await fs.readFile(filePath, 'utf-8');
    return content;
  } catch (error) {
    console.error('Error en readWikiPage:', error);
    throw new Error(`No se pudo leer la página de conocimiento '${pageName}'. Asegúrate de que existe.`);
  }
}

/**
 * Obtiene los últimos pedidos de un cliente por su número de teléfono.
 * @param {string} clientPhone - Teléfono del cliente
 */
export async function getClientOrders(clientPhone) {
  try {
    if (!clientPhone) return [];
    
    // Limpiar el teléfono para dejar solo dígitos
    const digits = clientPhone.replace(/\D/g, '');
    if (digits.length < 6) return []; 
    
    // Tomar los últimos 9 dígitos para la búsqueda flexible (típico en Chile: 9XXXXYYYY)
    const last9 = digits.slice(-9);
    const searchPattern1 = `%${last9}`;
    const searchPattern2 = `%${digits}`;

    const [rows] = await pool.execute(
      `SELECT p.id, p.nombre_cliente, p.direccion, p.ciudad, p.total, p.estado, p.metodo_entrega, p.creado_en, 
              p.fecha_despacho, p.hora_despacho, p.notas,
              (SELECT GROUP_CONCAT(CONCAT(pi.cantidad, 'x ', pi.nombre) SEPARATOR ', ') 
               FROM pedido_items pi 
               WHERE pi.pedido_id = p.id) as productos
       FROM pedidos p
       WHERE p.telefono LIKE ? OR p.telefono LIKE ?
       ORDER BY p.creado_en DESC
       LIMIT 5`,
      [searchPattern1, searchPattern2]
    );
    return rows;
  } catch (error) {
    console.error('Error en getClientOrders:', error);
    throw new Error('No se pudo consultar el historial de pedidos.');
  }
}

/**
 * Actualiza los detalles de despacho (fecha, rango de hora, notas) del último pedido del cliente.
 * @param {string} clientPhone - Teléfono del cliente (WhatsApp)
 * @param {string} [fechaDespacho] - Fecha en formato YYYY-MM-DD
 * @param {string} [horaDespacho] - Rango horario (ej: "14:00 - 16:00")
 * @param {string} [notas] - Notas de despacho (ej: "casa azul de dos pisos")
 */
export async function updateLastOrderDeliveryDetails(clientPhone, fechaDespacho, horaDespacho, notas) {
  try {
    if (!clientPhone) {
      return { success: false, message: 'Número de teléfono del cliente no proporcionado.' };
    }

    const digits = clientPhone.replace(/\D/g, '');
    if (digits.length < 6) {
      return { success: false, message: 'Número de teléfono del cliente inválido.' };
    }

    const last9 = digits.slice(-9);
    const searchPattern1 = `%${last9}`;
    const searchPattern2 = `%${digits}`;

    // Buscar el último pedido de este cliente con sus detalles anteriores
    const [orders] = await pool.execute(
      `SELECT id, nombre_cliente, telefono, fecha_despacho, hora_despacho, notas FROM pedidos 
       WHERE telefono LIKE ? OR telefono LIKE ? 
       ORDER BY creado_en DESC 
       LIMIT 1`,
      [searchPattern1, searchPattern2]
    );

    if (orders.length === 0) {
      return { success: false, message: 'No se encontró ningún pedido para este cliente.' };
    }

    const order = orders[0];
    const orderId = order.id;
    const fields = [];
    const params = [];

    if (fechaDespacho !== undefined && fechaDespacho !== null) {
      fields.push('fecha_despacho = ?');
      params.push(fechaDespacho);
    }
    if (horaDespacho !== undefined && horaDespacho !== null) {
      fields.push('hora_despacho = ?');
      params.push(horaDespacho);
    }
    if (notas !== undefined && notas !== null) {
      fields.push('notas = ?');
      params.push(notas);
    }

    if (fields.length === 0) {
      return { success: false, message: 'No se proporcionaron datos para actualizar.' };
    }

    params.push(orderId);
    const query = `UPDATE pedidos SET ${fields.join(', ')} WHERE id = ?`;
    await pool.execute(query, params);

    // Emitir el evento de actualización para que index.js notifique al administrador
    orderEvents.emit('orderUpdated', {
      orderId,
      nombreCliente: order.nombre_cliente,
      clientPhone: order.telefono,
      oldDetails: {
        fechaDespacho: order.fecha_despacho,
        horaDespacho: order.hora_despacho,
        notas: order.notas
      },
      newDetails: {
        fechaDespacho: fechaDespacho !== undefined && fechaDespacho !== null ? fechaDespacho : order.fecha_despacho,
        horaDespacho: horaDespacho !== undefined && horaDespacho !== null ? horaDespacho : order.hora_despacho,
        notas: notas !== undefined && notas !== null ? notas : order.notas
      }
    });

    return { 
      success: true, 
      message: `Detalles de despacho del pedido #${orderId} actualizados con éxito.`,
      orderId,
      updatedFields: { fechaDespacho, horaDespacho, notas }
    };
  } catch (error) {
    console.error('Error en updateLastOrderDeliveryDetails:', error);
    throw new Error('No se pudieron actualizar los detalles del despacho del último pedido.');
  }
}

/**
 * Registra un nuevo pedido y sus ítems en la base de datos bajo una transacción SQL.
 * @param {object} orderData - Datos estructurados del pedido
 */
export async function createOrder(orderData) {
  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const {
      clientPhone,
      nombreCliente,
      emailCliente = 'ventas_whatsapp@mascotiendas.cl',
      direccion,
      ciudad = 'La Serena',
      subtotal,
      descuento = 0,
      total,
      metodoEntrega = 'delivery',
      zonaDeliveryId = null,
      costoDelivery = 0,
      fechaDespacho = null,
      horaDespacho = null,
      notas = '',
      items = []
    } = orderData;

    // Normalizar el teléfono antes de guardar en la BD
    // contact.number de whatsapp-web.js retorna solo dígitos (ej: '56949865594')
    // Aseguramos formato +56XXXXXXXXX guardando con el + para consistencia
    const rawPhone = String(clientPhone).replace(/\D/g, '');
    const normalizedPhone = rawPhone.startsWith('56') && rawPhone.length >= 11
      ? `+${rawPhone}`
      : rawPhone.length === 9
        ? `+56${rawPhone}`
        : `+${rawPhone}`;

    // 1. Insertar el pedido principal
    const [result] = await connection.execute(
      `INSERT INTO pedidos (
        nombre_cliente, email_cliente, telefono, direccion, ciudad, 
        subtotal, descuento, total, estado, metodo_pago, 
        zona_delivery_id, costo_delivery, fecha_despacho, hora_despacho, 
        metodo_entrega, notas, creado_en
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', 'flow', ?, ?, ?, ?, ?, ?, NOW())`,
      [
        nombreCliente, emailCliente, normalizedPhone, direccion, ciudad,
        subtotal, descuento, total, zonaDeliveryId, costoDelivery,
        fechaDespacho, horaDespacho, metodoEntrega, notas
      ]
    );

    const pedidoId = result.insertId;

    // 2. Insertar los ítems del pedido
    for (const item of items) {
      const { productoId, nombre, precio, cantidad = 1, imagenUrl = null } = item;
      await connection.execute(
        `INSERT INTO pedido_items (pedido_id, producto_id, nombre, precio, cantidad, imagen_url)
         VALUES (?, ?, ?, ?, ?, ?)`,
        [pedidoId, productoId, nombre, precio, cantidad, imagenUrl]
      );
    }

    await connection.commit();

    // Emitir evento de pedido creado para notificar por WhatsApp
    try {
      orderEvents.emit('orderCreated', {
        orderId: pedidoId,
        clientPhone,
        nombreCliente,
        emailCliente,
        direccion,
        ciudad,
        subtotal,
        descuento,
        total,
        metodoEntrega,
        zonaDeliveryId,
        costoDelivery,
        fechaDespacho,
        horaDespacho,
        notas,
        items
      });
    } catch (e) {
      console.error('Error al emitir evento orderCreated:', e);
    }

    return {
      success: true,
      message: `Pedido #${pedidoId} creado y registrado con éxito.`,
      pedidoId,
      total
    };
  } catch (error) {
    await connection.rollback();
    console.error('Error en createOrder:', error);
    throw new Error('No se pudo registrar el pedido en la base de datos.');
  } finally {
    connection.release();
  }
}

/**
 * Anula un pedido específico o el último pedido pendiente de un cliente.
 * @param {string} clientPhone - Teléfono del cliente
 * @param {number} [orderId] - ID opcional del pedido a anular
 */
export async function cancelClientOrder(clientPhone, orderId) {
  try {
    if (!clientPhone) {
      return { success: false, message: 'Número de teléfono del cliente no proporcionado.' };
    }

    const digits = clientPhone.replace(/\D/g, '');
    if (digits.length < 6) {
      return { success: false, message: 'Número de teléfono del cliente inválido.' };
    }

    const last9 = digits.slice(-9);
    const searchPattern1 = `%${last9}`;
    const searchPattern2 = `%${digits}`;

    let order;

    if (orderId) {
      // Buscar pedido específico que pertenezca al cliente
      const [rows] = await pool.execute(
        `SELECT id, nombre_cliente, telefono, total, estado FROM pedidos 
         WHERE id = ? AND (telefono LIKE ? OR telefono LIKE ?)`,
        [orderId, searchPattern1, searchPattern2]
      );
      if (rows.length === 0) {
        return { success: false, message: `No se encontró ningún pedido #${orderId} asociado a tu número.` };
      }
      order = rows[0];
    } else {
      // Buscar el último pedido del cliente con estado 'pendiente'
      const [rows] = await pool.execute(
        `SELECT id, nombre_cliente, telefono, total, estado FROM pedidos 
         WHERE (telefono LIKE ? OR telefono LIKE ?) AND estado = 'pendiente'
         ORDER BY creado_en DESC 
         LIMIT 1`,
        [searchPattern1, searchPattern2]
      );
      if (rows.length === 0) {
        return { success: false, message: 'No tienes ningún pedido pendiente que se pueda anular.' };
      }
      order = rows[0];
    }

    if (order.estado !== 'pendiente') {
      return { success: false, message: `El pedido #${order.id} ya se encuentra en estado '${order.estado}' y no puede ser anulado.` };
    }

    // Actualizar el estado a 'cancelado'
    await pool.execute(
      `UPDATE pedidos SET estado = 'cancelado', actualizado_en = NOW() WHERE id = ?`,
      [order.id]
    );

    // Emitir el evento de anulación
    try {
      orderEvents.emit('orderCancelled', {
        orderId: order.id,
        clientPhone: order.telefono,
        nombreCliente: order.nombre_cliente,
        total: order.total
      });
    } catch (e) {
      console.error('Error al emitir evento orderCancelled:', e);
    }

    return { 
      success: true, 
      message: `El pedido #${order.id} ha sido anulado con éxito.`,
      orderId: order.id,
      nombreCliente: order.nombre_cliente,
      total: order.total
    };
  } catch (error) {
    console.error('Error en cancelClientOrder:', error);
    throw new Error('No se pudo anular el pedido en la base de datos.');
  }
}




