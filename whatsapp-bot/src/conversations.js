/**
 * conversations.js — Estado en memoria de las conversaciones activas
 *
 * Cada entrada tiene:
 *   - client: objeto cliente de MAM (null si no identificado)
 *   - messages: historial de mensajes para Claude [ {role, content} ]
 *   - pendingBudget: items acumulados antes de confirmar el presupuesto
 *   - lastActivity: timestamp para limpiar sesiones viejas
 */

const TIMEOUT_MS = 30 * 60 * 1000; // 30 minutos de inactividad

const sessions = new Map();

function get(phone) {
    return sessions.get(phone) || null;
}

function set(phone, data) {
    sessions.set(phone, { ...data, lastActivity: Date.now() });
}

function getOrCreate(phone) {
    if (!sessions.has(phone)) {
        sessions.set(phone, {
            client: null,
            messages: [],
            pendingBudget: [],
            lastActivity: Date.now()
        });
    }
    return sessions.get(phone);
}

function addMessage(phone, role, content) {
    const session = getOrCreate(phone);
    session.messages.push({ role, content });
    session.lastActivity = Date.now();
    // Mantener historial acotado (últimos 20 mensajes)
    if (session.messages.length > 20) session.messages.splice(0, session.messages.length - 20);
}

function clear(phone) {
    sessions.delete(phone);
}

// Limpieza periódica de sesiones inactivas
setInterval(() => {
    const now = Date.now();
    for (const [phone, session] of sessions.entries()) {
        if (now - session.lastActivity > TIMEOUT_MS) {
            sessions.delete(phone);
        }
    }
}, 5 * 60 * 1000);

module.exports = { get, set, getOrCreate, addMessage, clear };
