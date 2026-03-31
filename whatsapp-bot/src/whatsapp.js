/**
 * whatsapp.js — Cliente para la API de Meta (WhatsApp Business)
 */

const axios = require('axios');

const BASE = `https://graph.facebook.com/v19.0/${process.env.PHONE_NUMBER_ID}`;

const HEADERS = () => ({
    Authorization: `Bearer ${process.env.WHATSAPP_TOKEN}`,
    'Content-Type': 'application/json'
});

async function sendText(to, text) {
    await axios.post(
        `${BASE}/messages`,
        { messaging_product: 'whatsapp', to, type: 'text', text: { body: text } },
        { headers: HEADERS() }
    );
}

/**
 * Envía una imagen con caption opcional.
 * imageUrl debe ser una URL pública accesible por los servidores de Meta.
 */
async function sendImage(to, imageUrl, caption = '') {
    await axios.post(
        `${BASE}/messages`,
        {
            messaging_product: 'whatsapp',
            to,
            type: 'image',
            image: { link: imageUrl, caption }
        },
        { headers: HEADERS() }
    );
}

/**
 * Envía múltiples imágenes en secuencia.
 * Útil para mostrar varias promociones de una vez.
 */
async function sendImages(to, items) {
    for (const item of items) {
        await sendImage(to, item.url, item.caption || '');
    }
}

module.exports = { sendText, sendImage, sendImages };
