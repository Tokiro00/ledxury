/**
 * index.js — Servidor principal del bot de WhatsApp MAM
 *
 * Endpoints:
 *   GET  /webhook  — Verificación inicial de Meta
 *   POST /webhook  — Recepción de mensajes entrantes
 *   GET  /health   — Health check
 */

require('dotenv').config();

const express = require('express');
const conversations = require('./src/conversations');
const claude = require('./src/claude');
const mam = require('./src/mam');
const whatsapp = require('./src/whatsapp');

const app = express();
app.use(express.json());

// ---------------------------------------------------------------
// GET /webhook — Verificación de Meta
// Meta llama este endpoint una sola vez al registrar el webhook.
// ---------------------------------------------------------------
app.get('/webhook', (req, res) => {
    const mode      = req.query['hub.mode'];
    const token     = req.query['hub.verify_token'];
    const challenge = req.query['hub.challenge'];

    if (mode === 'subscribe' && token === process.env.VERIFY_TOKEN) {
        console.log('Webhook verificado por Meta ✓');
        return res.status(200).send(challenge);
    }

    res.sendStatus(403);
});

// ---------------------------------------------------------------
// POST /webhook — Mensajes entrantes de WhatsApp
// ---------------------------------------------------------------
app.post('/webhook', async (req, res) => {
    // Responder 200 inmediatamente para que Meta no reintente
    res.sendStatus(200);

    try {
        const body = req.body;

        if (body.object !== 'whatsapp_business_account') return;

        const changes = body.entry?.[0]?.changes?.[0];
        const message = changes?.value?.messages?.[0];

        if (!message || message.type !== 'text') return;

        const phone       = message.from;                  // ej: "573001234567"
        const userMessage = message.text.body.trim();

        console.log(`[${phone}] → ${userMessage}`);

        // Obtener o crear sesión
        const session = conversations.getOrCreate(phone);

        // Identificar cliente en MAM si aún no está en sesión
        if (!session.client) {
            try {
                session.client = await mam.getClientByPhone(phone);
            } catch (_) {
                session.client = null;
            }
        }

        // Procesar con Claude
        const reply = await claude.processMessage(phone, userMessage, session);

        // Guardar sesión actualizada
        conversations.set(phone, session);

        // Enviar respuesta al cliente
        if (reply) {
            console.log(`[${phone}] ← ${reply}`);
            await whatsapp.sendText(phone, reply);
        }

    } catch (err) {
        console.error('Error procesando mensaje:', err.message);
    }
});

// ---------------------------------------------------------------
// GET /health
// ---------------------------------------------------------------
app.get('/health', (_req, res) => {
    res.json({ status: 'ok', uptime: process.uptime() });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Bot MAM escuchando en puerto ${PORT}`);
    console.log(`Webhook URL: https://TU_DOMINIO/webhook`);
});
