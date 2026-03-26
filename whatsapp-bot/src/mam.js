/**
 * mam.js — Cliente para la API REST de MAM ERP
 *
 * Maneja autenticación JWT (con renovación automática) y expone
 * las funciones que el bot necesita para atender clientes.
 */

const axios = require('axios');

const BASE_URL = process.env.MAM_API_URL;
let _token = null;
let _tokenExpires = 0;

async function _getToken() {
    if (_token && Date.now() < _tokenExpires) return _token;

    const res = await axios.post(`${BASE_URL}/api/v1/login`, {
        uid: process.env.MAM_API_USER,
        password: process.env.MAM_API_PASSWORD
    });

    _token = res.data.data.token;
    // JWT dura 7 días, refrescamos cada 6
    _tokenExpires = Date.now() + 6 * 24 * 60 * 60 * 1000;
    return _token;
}

async function _get(path, params = {}) {
    const token = await _getToken();
    const res = await axios.get(`${BASE_URL}${path}`, {
        headers: { Authorization: `Bearer ${token}` },
        params
    });
    return res.data.data;
}

async function _post(path, body = {}) {
    const token = await _getToken();
    const res = await axios.post(`${BASE_URL}${path}`, body, {
        headers: { Authorization: `Bearer ${token}` }
    });
    return res.data.data;
}

// Identifica al cliente por número de WhatsApp
async function getClientByPhone(phone) {
    try {
        return await _get('/api/v1/clients/by-phone', { phone });
    } catch (e) {
        if (e.response?.status === 404) return null;
        throw e;
    }
}

// Saldo de cartera del cliente (incluido en getClientByPhone como .balance)
// Esta función es para refrescar el saldo individual si ya tienes el id
async function getCartera(clientId) {
    const data = await _get('/api/v1/cartera');
    const found = data.clients.find(c => c.idClient == clientId);
    return found || null;
}

// Busca productos por término
async function searchProducts(term, limit = 5) {
    const data = await _get('/api/v1/products/search', { q: term, limit });
    return data.products || [];
}

// Lista promociones activas
async function getPromotions() {
    const data = await _get('/api/v1/promotions');
    return data.promotions || [];
}

// Crea un presupuesto borrador
// items: [{ productId, quantity, price }]
async function createBudget({ clientId, storeId, vendorId, items, notes = '' }) {
    return await _post('/api/v1/budgets/sync', {
        clientId,
        storeId,
        vendorId,
        items,
        notes,
        budget_type: 'venta'
    });
}

module.exports = { getClientByPhone, getCartera, searchProducts, getPromotions, createBudget };
