/**
 * claude.js — Integración con Claude API (Anthropic)
 *
 * Claude actúa como el cerebro del bot:
 *   - Entiende la intención del cliente en lenguaje natural
 *   - Llama las herramientas de MAM cuando las necesita
 *   - Genera respuestas naturales en español colombiano
 */

const Anthropic = require('@anthropic-ai/sdk');
const mam = require('./mam');
const whatsapp = require('./whatsapp');

const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

const TOOLS = [
    {
        name: 'buscar_productos',
        description: 'Busca productos en el catálogo por nombre o referencia. Úsala cuando el cliente pregunta por un producto específico o quiere ver opciones.',
        input_schema: {
            type: 'object',
            properties: {
                termino: { type: 'string', description: 'Nombre, referencia o descripción del producto a buscar' }
            },
            required: ['termino']
        }
    },
    {
        name: 'ver_cartera',
        description: 'Consulta el saldo pendiente del cliente actual. Úsala cuando el cliente pregunta cuánto debe, sus facturas pendientes o su estado de cuenta.',
        input_schema: {
            type: 'object',
            properties: {},
            required: []
        }
    },
    {
        name: 'ver_promociones',
        description: 'Lista las promociones y paquetes especiales activos. Úsala cuando el cliente pregunta por ofertas, promociones o combos.',
        input_schema: {
            type: 'object',
            properties: {},
            required: []
        }
    },
    {
        name: 'crear_presupuesto',
        description: 'Crea un presupuesto borrador con los productos y cantidades que el cliente solicitó. Solo llama esta herramienta cuando el cliente haya confirmado explícitamente los productos y cantidades.',
        input_schema: {
            type: 'object',
            properties: {
                items: {
                    type: 'array',
                    description: 'Lista de productos del presupuesto',
                    items: {
                        type: 'object',
                        properties: {
                            productId: { type: 'string', description: 'ID del producto' },
                            descripcion: { type: 'string', description: 'Nombre del producto' },
                            quantity: { type: 'number', description: 'Cantidad solicitada' },
                            price: { type: 'number', description: 'Precio unitario' }
                        },
                        required: ['productId', 'quantity', 'price']
                    }
                },
                notas: { type: 'string', description: 'Notas adicionales del cliente' }
            },
            required: ['items']
        }
    }
];

function buildSystemPrompt(session) {
    const clientInfo = session.client
        ? `El cliente se llama ${session.client.client.name} (ID: ${session.client.client.idClient}). Saldo actual: $${Number(session.client.balance).toLocaleString('es-CO')}.`
        : 'El cliente aún no está identificado en el sistema (no se encontró por número de teléfono). Salúdalo cordialmente y ofrece ayuda general.';

    return `Eres el asistente virtual de MAM, una empresa colombiana de distribución. Atiendes clientes por WhatsApp.

${clientInfo}

Instrucciones:
- Responde siempre en español colombiano, de forma amable y concisa.
- Los mensajes de WhatsApp deben ser cortos (máximo 3-4 líneas por mensaje).
- Usa emojis con moderación (1-2 por mensaje).
- Para precios, usa formato colombiano: $1.450.000
- Cuando listes productos, muestra máximo 3-4 opciones con nombre y precio.
- Nunca asumas la categoría o tipo de producto — usa siempre la herramienta buscar_productos.
- Si el cliente quiere cotizar, recoge los productos y cantidades antes de crear el presupuesto.
- Los presupuestos quedan como borradores; un asesor los confirma.
- No inventes precios ni disponibilidad — usa siempre las herramientas.
- Si no puedes ayudar con algo, sugiere contactar directamente a un asesor.`;
}

async function processMessage(phone, userMessage, session) {
    // Agregar mensaje del usuario al historial
    session.messages.push({ role: 'user', content: userMessage });

    const response = await client.messages.create({
        model: 'claude-sonnet-4-6',
        max_tokens: 1024,
        system: buildSystemPrompt(session),
        tools: TOOLS,
        messages: session.messages
    });

    // Procesar respuesta — puede incluir llamadas a herramientas
    let finalText = '';
    const assistantContent = response.content;

    // Agregar respuesta del asistente al historial
    session.messages.push({ role: 'assistant', content: assistantContent });

    // Si Claude no llamó ninguna herramienta, retornar el texto directamente
    if (response.stop_reason !== 'tool_use') {
        finalText = assistantContent.map(b => b.type === 'text' ? b.text : '').join('').trim();
        return finalText;
    }

    // Ejecutar herramientas y continuar la conversación
    const toolResults = [];

    for (const block of assistantContent) {
        if (block.type !== 'tool_use') continue;

        let result;
        try {
            result = await _runTool(block.name, block.input, session, phone);
        } catch (err) {
            result = { error: 'No pude obtener esa información en este momento.' };
        }

        toolResults.push({
            type: 'tool_result',
            tool_use_id: block.id,
            content: JSON.stringify(result)
        });
    }

    // Segunda llamada con los resultados de las herramientas
    session.messages.push({ role: 'user', content: toolResults });

    const followUp = await client.messages.create({
        model: 'claude-sonnet-4-6',
        max_tokens: 1024,
        system: buildSystemPrompt(session),
        tools: TOOLS,
        messages: session.messages
    });

    session.messages.push({ role: 'assistant', content: followUp.content });

    finalText = followUp.content
        .filter(b => b.type === 'text')
        .map(b => b.text)
        .join('')
        .trim();

    return finalText;
}

async function _runTool(name, input, session, phone) {
    switch (name) {
        case 'buscar_productos': {
            const products = await mam.searchProducts(input.termino, 4);
            if (!products.length) return { mensaje: 'No encontré productos con ese término.' };

            // Seleccionar precio según la tarifa del cliente (rate)
            const clientRate = session.client?.client?.rate || 1;
            const rateMap = { 1: 'price', 2: 'price_base', 3: 'price_scale', 4: 'price_dist' };
            const priceField = rateMap[clientRate] || 'price';

            return {
                productos: products.map(p => ({
                    id: p.idProduct,
                    nombre: p.description,
                    precio: p[priceField] || p.price,
                    stock: p.total_stock
                }))
            };
        }

        case 'ver_cartera': {
            if (!session.client) return { mensaje: 'No pude identificar tu cuenta.' };
            return {
                saldo: session.client.balance,
                nombre: session.client.client.name
            };
        }

        case 'ver_promociones': {
            const promos = await mam.getPromotions();
            if (!promos.length) return { mensaje: 'No hay promociones activas en este momento.' };

            // Enviar cada imagen directamente por WhatsApp antes de que Claude responda el texto
            for (const promo of promos) {
                if (promo.image_url) {
                    const caption = promo.name
                        ? `*${promo.name}*\n${promo.comments || ''}`
                        : promo.comments || '';
                    await whatsapp.sendImage(phone, promo.image_url, caption.trim());
                }
            }

            // Devolver a Claude solo los nombres para que genere el texto de cierre
            return {
                promociones: promos.map(p => ({ id: p.idPromopack, nombre: p.name, descripcion: p.comments })),
                imagenes_enviadas: promos.filter(p => p.image_url).length
            };
        }

        case 'crear_presupuesto': {
            if (!session.client) return { error: 'No pude identificar tu cuenta para crear el presupuesto.' };
            const clientData = session.client.client;
            // Usar la tienda del vendedor asignado al cliente (vendor_store viene del JOIN con users)
            const vendorStore = clientData.vendor_store || 1;
            const budget = await mam.createBudget({
                clientId: clientData.idClient,
                storeId: vendorStore,
                vendorId: clientData.vendor,
                items: input.items.map(i => ({
                    productId: i.productId,
                    quantity: i.quantity,
                    price: i.price
                })),
                notes: input.notas || ''
            });
            return {
                presupuestoId: budget.budgetId,
                total: budget.total,
                mensaje: `Presupuesto creado exitosamente`
            };
        }

        default:
            return { error: 'Herramienta no reconocida' };
    }
}

module.exports = { processMessage };
