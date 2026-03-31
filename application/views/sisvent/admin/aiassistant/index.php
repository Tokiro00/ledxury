<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$userName = $this->session->userdata('user_data')['name'];
$userInitial = strtoupper(mb_substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
    <title>Asistente IA - MAM</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="flex-1 overflow-hidden">
                <div class="flex h-full">

                    <!-- ═══ Conversation Sidebar ═══ -->
                    <div id="conv-sidebar" class="conv-sidebar flex-shrink-0 flex flex-col bg-white border-r border-gray-200">
                        <!-- New conversation button -->
                        <div class="p-3 border-b border-gray-100">
                            <button id="btn-new-conv" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium transition-all hover:opacity-90" style="background:#2E7D91;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Nueva conversacion
                            </button>
                        </div>
                        <!-- Conversations list -->
                        <div id="conv-list" class="flex-1 overflow-y-auto">
                            <!-- Populated via JS -->
                        </div>
                        <!-- Mobile close -->
                        <div class="p-3 border-t border-gray-100 md:hidden">
                            <button id="btn-close-sidebar" class="w-full text-center text-xs text-gray-400 py-1">Cerrar</button>
                        </div>
                    </div>

                    <!-- ═══ Chat Main Area ═══ -->
                    <div class="flex-1 flex flex-col min-w-0">

                        <!-- Chat header -->
                        <div class="px-4 py-3 flex items-center gap-3 border-b border-gray-200 bg-white flex-shrink-0">
                            <!-- Mobile hamburger -->
                            <button id="btn-toggle-sidebar" class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:linear-gradient(135deg,#2E7D91,#1B365D);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-1.5 4.5H6.5L5 14.5m14 0H5"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h2 id="chat-title" class="text-sm font-semibold text-gray-800 truncate">Asistente MAM</h2>
                                <p class="text-xs text-gray-400">Conectado a datos en tiempo real</p>
                            </div>
                            <div class="ml-auto flex items-center gap-1 flex-shrink-0">
                                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                <span class="text-xs text-gray-400">Online</span>
                            </div>
                        </div>

                        <!-- Messages area -->
                        <div id="chat-messages" class="flex-1 overflow-y-auto" style="background:#f8f9fb;">
                            <!-- Welcome / quick actions shown when no conversation loaded -->
                            <div id="welcome-screen" class="flex items-center justify-center h-full px-6">
                                <div class="max-w-2xl w-full text-center">
                                    <div class="w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center" style="background:linear-gradient(135deg,#2E7D91,#1B365D);">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 00.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-1.5 4.5H6.5L5 14.5m14 0H5"/></svg>
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Hola, <?= htmlspecialchars($userName) ?></h2>
                                    <p class="text-sm text-gray-500 mb-8">Puedo consultar informacion en tiempo real sobre tu negocio.</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Cuanto vendimos este mes?">
                                            <span class="block font-medium mb-0.5">Ventas del mes</span>
                                            <span class="text-gray-400">Resumen de facturacion</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Quienes son los principales deudores?">
                                            <span class="block font-medium mb-0.5">Top deudores</span>
                                            <span class="text-gray-400">Cartera pendiente</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Que productos estan agotados?">
                                            <span class="block font-medium mb-0.5">Inventario critico</span>
                                            <span class="text-gray-400">Productos sin stock</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Dame un resumen general del negocio">
                                            <span class="block font-medium mb-0.5">Resumen general</span>
                                            <span class="text-gray-400">Vision global</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Cuales son las ventas por vendedor este mes?">
                                            <span class="block font-medium mb-0.5">Ranking vendedores</span>
                                            <span class="text-gray-400">Desempeno comercial</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Como esta la cartera vencida?">
                                            <span class="block font-medium mb-0.5">Cartera vencida</span>
                                            <span class="text-gray-400">Deudas por cobrar</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Cual es el estado de cajas y bancos?">
                                            <span class="block font-medium mb-0.5">Cajas y bancos</span>
                                            <span class="text-gray-400">Saldos disponibles</span>
                                        </button>
                                        <button class="quick-action text-left text-xs px-4 py-3 rounded-xl border border-gray-200 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition-all" data-question="Cuales son los gastos del mes?">
                                            <span class="block font-medium mb-0.5">Gastos del mes</span>
                                            <span class="text-gray-400">Egresos operativos</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Actual messages container (hidden when welcome visible) -->
                            <div id="messages-container" class="px-6 py-6 space-y-5 hidden">
                            </div>
                        </div>

                        <!-- Input area -->
                        <div class="px-4 py-3 bg-white border-t border-gray-200 flex-shrink-0">
                            <form id="chat-form" class="flex items-end gap-3 max-w-4xl mx-auto">
                                <div class="flex-1 relative">
                                    <textarea
                                        id="chat-input"
                                        rows="1"
                                        class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl resize-none focus:outline-none focus:bg-white focus:border-gray-400 focus:shadow-sm transition-all"
                                        placeholder="Escribe tu mensaje..."
                                        style="max-height:130px;"
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    id="chat-send"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl text-white transition-all duration-150 hover:opacity-90 flex-shrink-0 mb-0.5"
                                    style="background:#2E7D91;"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<style>
/* ─── Conversation Sidebar ─── */
.conv-sidebar {
    width: 280px;
    transition: transform 0.2s ease;
}
@media (max-width: 767px) {
    .conv-sidebar {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        z-index: 40;
        transform: translateX(-100%);
        box-shadow: 2px 0 12px rgba(0,0,0,0.08);
    }
    .conv-sidebar.open {
        transform: translateX(0);
    }
}

/* ─── Conversation items ─── */
.conv-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    cursor: pointer;
    border-left: 3px solid transparent;
    transition: all 0.15s;
    position: relative;
}
.conv-item:hover {
    background: #f3f4f6;
}
.conv-item.active {
    background: #e8f4f8;
    border-left-color: #2E7D91;
}
.conv-item .conv-delete {
    display: none;
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #fee2e2;
    color: #ef4444;
    font-size: 14px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
}
.conv-item:hover .conv-delete {
    display: block;
}
.conv-item .conv-delete:hover {
    background: #fecaca;
}

/* ─── Messages ─── */
.msg-user {
    display: flex;
    justify-content: flex-end;
}
.msg-user .msg-bubble {
    background: #2E7D91;
    color: #fff;
    border-radius: 18px 18px 4px 18px;
    padding: 12px 18px;
    max-width: 75%;
    font-size: 15px;
    line-height: 1.5;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    word-wrap: break-word;
}
.msg-assistant {
    display: flex;
    justify-content: flex-start;
}
.msg-assistant .msg-bubble {
    background: #f7f8fa;
    color: #1a1a2e;
    border-radius: 18px 18px 18px 4px;
    padding: 14px 20px;
    max-width: 85%;
    font-size: 15px;
    line-height: 1.6;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    word-wrap: break-word;
}

/* ─── Markdown in assistant messages ─── */
.msg-bubble.ai-content h2 { font-size: 1.15em; font-weight: 700; margin: 16px 0 8px; color: #1a1a2e; }
.msg-bubble.ai-content h3 { font-size: 1.05em; font-weight: 700; margin: 14px 0 6px; color: #1a1a2e; }
.msg-bubble.ai-content h4 { font-size: 1em; font-weight: 600; margin: 12px 0 4px; color: #1a1a2e; }
.msg-bubble.ai-content strong { font-weight: 600; }
.msg-bubble.ai-content ul { list-style: disc; margin: 8px 0 8px 20px; }
.msg-bubble.ai-content ol { list-style: decimal; margin: 8px 0 8px 20px; }
.msg-bubble.ai-content li { margin-bottom: 4px; }
.msg-bubble.ai-content p { margin-bottom: 8px; }
.msg-bubble.ai-content p:last-child { margin-bottom: 0; }
.msg-bubble.ai-content code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: "SF Mono", "Fira Code", "Fira Mono", Menlo, monospace;
    font-size: 0.88em;
}
.msg-bubble.ai-content pre {
    background: #1a1a2e;
    color: #e2e8f0;
    padding: 14px 16px;
    border-radius: 10px;
    overflow-x: auto;
    margin: 10px 0;
    font-size: 0.85em;
    line-height: 1.5;
}
.msg-bubble.ai-content pre code {
    background: none;
    padding: 0;
    color: inherit;
    font-size: inherit;
}
.msg-bubble.ai-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    font-size: 0.9em;
}
.msg-bubble.ai-content table th {
    background: #e5e7eb;
    font-weight: 600;
    text-align: left;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
}
.msg-bubble.ai-content table td {
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
}
.msg-bubble.ai-content table tr:nth-child(even) td {
    background: #f9fafb;
}
.msg-bubble.ai-content hr {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 12px 0;
}

/* ─── Typing indicator ─── */
.typing-dots {
    display: flex;
    gap: 5px;
    align-items: center;
}
.typing-dots span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2E7D91;
    animation: typing 1.4s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing {
    0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
    30% { opacity: 1; transform: scale(1); }
}

/* ─── Textarea auto-resize ─── */
#chat-input {
    overflow-y: hidden;
}
</style>

<script>
(function() {
    var BASE = '<?= base_url() ?>sisvent/admin/aiassistant/';
    var currentConvId = null;
    var isLoading = false;

    // ─── Load conversations on page load ───
    function loadConversations(selectId) {
        $.ajax({
            url: BASE + 'getConversations',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (!data.success) return;
                var html = '';
                if (data.conversations.length === 0) {
                    html = '<div class="px-4 py-8 text-center text-xs text-gray-400">Sin conversaciones previas</div>';
                } else {
                    $.each(data.conversations, function(i, c) {
                        var active = (selectId && c.id == selectId) ? ' active' : '';
                        var dateStr = formatConvDate(c.updated_at);
                        html += '<div class="conv-item' + active + '" data-id="' + c.id + '">' +
                            '<div class="flex-1 min-w-0 pr-6">' +
                            '<div class="text-sm text-gray-700 truncate" style="font-weight:500;">' + escapeHtml(c.title) + '</div>' +
                            '<div class="text-xs text-gray-400 mt-0.5">' + dateStr + '</div>' +
                            '</div>' +
                            '<div class="conv-delete" data-id="' + c.id + '" title="Eliminar">&times;</div>' +
                            '</div>';
                    });
                }
                $('#conv-list').html(html);
            }
        });
    }

    function formatConvDate(dt) {
        if (!dt) return '';
        var d = new Date(dt.replace(' ', 'T'));
        var now = new Date();
        var diff = Math.floor((now - d) / 86400000);
        if (diff === 0) return 'Hoy';
        if (diff === 1) return 'Ayer';
        if (diff < 7) return 'Hace ' + diff + ' dias';
        return d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' });
    }

    // ─── Load messages for a conversation ───
    function loadConversation(convId) {
        currentConvId = convId;
        $('#welcome-screen').addClass('hidden');
        $('#messages-container').removeClass('hidden').html('');

        // Mark active in sidebar
        $('#conv-list .conv-item').removeClass('active');
        $('#conv-list .conv-item[data-id="' + convId + '"]').addClass('active');

        $.ajax({
            url: BASE + 'getMessages',
            method: 'GET',
            data: { conversationId: convId },
            dataType: 'json',
            success: function(data) {
                if (!data.success) return;
                $('#chat-title').text(data.conversation.title || 'Asistente MAM');
                $.each(data.messages, function(i, m) {
                    appendMessage(m.role, m.content);
                });
                scrollToBottom();
            }
        });
    }

    // ─── Append a message to the chat ───
    function appendMessage(role, content) {
        var html = '';
        if (role === 'user') {
            html = '<div class="msg-user">' +
                '<div class="msg-bubble">' + escapeHtml(content) + '</div>' +
                '</div>';
        } else {
            html = '<div class="msg-assistant">' +
                '<div class="msg-bubble ai-content">' + formatMarkdown(content) + '</div>' +
                '</div>';
        }
        $('#messages-container').append(html);
    }

    // ─── Send message ───
    function sendMessage(question) {
        if (isLoading || !question.trim()) return;
        isLoading = true;

        // Show messages area
        $('#welcome-screen').addClass('hidden');
        $('#messages-container').removeClass('hidden');

        // Append user message
        appendMessage('user', question);
        scrollToBottom();

        // Show loading indicator
        var loadingHtml = '<div id="typing-indicator" class="msg-assistant">' +
            '<div class="msg-bubble" style="padding:12px 20px;">' +
            '<div class="flex items-center gap-2">' +
            '<div class="typing-dots"><span></span><span></span><span></span></div>' +
            '<span class="text-xs text-gray-400">Analizando datos...</span>' +
            '</div></div></div>';
        $('#messages-container').append(loadingHtml);
        scrollToBottom();

        // Disable input
        $('#chat-input').prop('disabled', true);
        $('#chat-send').prop('disabled', true).css('opacity', '0.5');

        $.ajax({
            url: BASE + 'ask',
            method: 'POST',
            data: { question: question, conversationId: currentConvId || '' },
            dataType: 'json',
            timeout: 120000,
            success: function(data) {
                $('#typing-indicator').remove();

                if (data.conversationId) {
                    var isNewConv = !currentConvId;
                    currentConvId = data.conversationId;

                    if (isNewConv) {
                        // Reload sidebar and select the new conversation
                        loadConversations(currentConvId);
                        var title = question.trim().substring(0, 50);
                        $('#chat-title').text(title);
                    }
                }

                if (data.success) {
                    appendMessage('assistant', data.response);
                } else {
                    var errHtml = '<div class="msg-assistant">' +
                        '<div class="msg-bubble" style="background:#fef2f2;border:1px solid #fecaca;">' +
                        '<span class="text-red-600 text-sm">' + escapeHtml(data.error || 'Error desconocido') + '</span>' +
                        '</div></div>';
                    $('#messages-container').append(errHtml);
                }
                scrollToBottom();
            },
            error: function() {
                $('#typing-indicator').remove();
                var errHtml = '<div class="msg-assistant">' +
                    '<div class="msg-bubble" style="background:#fef2f2;border:1px solid #fecaca;">' +
                    '<span class="text-red-600 text-sm">No se pudo conectar con el asistente. Verifica tu conexion.</span>' +
                    '</div></div>';
                $('#messages-container').append(errHtml);
                scrollToBottom();
            },
            complete: function() {
                isLoading = false;
                $('#chat-input').prop('disabled', false).focus();
                $('#chat-send').prop('disabled', false).css('opacity', '1');
            }
        });
    }

    // ─── Start a new conversation ───
    function startNewConversation() {
        currentConvId = null;
        $('#conv-list .conv-item').removeClass('active');
        $('#messages-container').addClass('hidden').html('');
        $('#welcome-screen').removeClass('hidden');
        $('#chat-title').text('Asistente MAM');
        $('#chat-input').val('').trigger('input').focus();
    }

    // ─── Utilities ───
    function scrollToBottom() {
        var el = document.getElementById('chat-messages');
        setTimeout(function() { el.scrollTop = el.scrollHeight; }, 50);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function formatMarkdown(text) {
        // Escape HTML first
        text = escapeHtml(text);

        // Code blocks (``` ... ```)
        text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, function(m, lang, code) {
            return '<pre><code>' + code.trim() + '</code></pre>';
        });

        // Inline code
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Tables
        text = text.replace(/((?:^\|.+\|$\n?)+)/gm, function(tableBlock) {
            var rows = tableBlock.trim().split('\n');
            if (rows.length < 2) return tableBlock;

            var html = '<table>';
            var isHeader = true;
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i].trim();
                // Skip separator rows (|---|---|)
                if (/^\|[\s\-:|]+\|$/.test(row)) {
                    isHeader = false;
                    continue;
                }
                var cells = row.split('|').filter(function(c, idx, arr) { return idx > 0 && idx < arr.length - 1; });
                var tag = isHeader ? 'th' : 'td';
                html += '<tr>';
                for (var j = 0; j < cells.length; j++) {
                    html += '<' + tag + '>' + cells[j].trim() + '</' + tag + '>';
                }
                html += '</tr>';
                if (isHeader && i === 0) {
                    // Next row might be separator; still mark as header candidate
                }
            }
            html += '</table>';
            return html;
        });

        // Headers
        text = text.replace(/^#### (.*?)$/gm, '<h4>$1</h4>');
        text = text.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
        text = text.replace(/^## (.*?)$/gm, '<h2>$1</h2>');

        // Horizontal rule
        text = text.replace(/^---$/gm, '<hr>');

        // Bold
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Italic
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Numbered lists
        text = text.replace(/((?:^\d+\. .+$\n?)+)/gm, function(block) {
            var items = block.trim().split('\n');
            var html = '<ol>';
            for (var i = 0; i < items.length; i++) {
                html += '<li>' + items[i].replace(/^\d+\.\s*/, '') + '</li>';
            }
            html += '</ol>';
            return html;
        });

        // Bullet lists
        text = text.replace(/((?:^[-*] .+$\n?)+)/gm, function(block) {
            var items = block.trim().split('\n');
            var html = '<ul>';
            for (var i = 0; i < items.length; i++) {
                html += '<li>' + items[i].replace(/^[-*]\s*/, '') + '</li>';
            }
            html += '</ul>';
            return html;
        });

        // Paragraphs (double newlines)
        text = text.replace(/\n\n/g, '</p><p>');
        text = text.replace(/\n/g, '<br>');

        // Wrap in paragraph
        if (text.indexOf('<h') !== 0 && text.indexOf('<table') !== 0 && text.indexOf('<ul') !== 0 && text.indexOf('<ol') !== 0 && text.indexOf('<pre') !== 0) {
            text = '<p>' + text + '</p>';
        }

        // Clean up empty paragraphs
        text = text.replace(/<p>\s*<\/p>/g, '');

        return text;
    }

    // ─── Event handlers (all delegated) ───

    // Submit form
    $(document).on('submit', '#chat-form', function(e) {
        e.preventDefault();
        var question = $('#chat-input').val().trim();
        if (!question) return;
        $('#chat-input').val('');
        autoResizeTextarea();
        sendMessage(question);
    });

    // Textarea: Enter sends, Shift+Enter newline, auto-resize
    $(document).on('keydown', '#chat-input', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#chat-form').trigger('submit');
        }
    });

    $(document).on('input', '#chat-input', function() {
        autoResizeTextarea();
    });

    function autoResizeTextarea() {
        var el = document.getElementById('chat-input');
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 130) + 'px';
    }

    // Quick action buttons
    $(document).on('click', '.quick-action', function() {
        var q = $(this).data('question');
        if (q) sendMessage(q);
    });

    // New conversation
    $(document).on('click', '#btn-new-conv', function() {
        startNewConversation();
    });

    // Click conversation in sidebar
    $(document).on('click', '.conv-item', function(e) {
        if ($(e.target).hasClass('conv-delete')) return;
        var id = $(this).data('id');
        if (id) loadConversation(id);
        // Close sidebar on mobile
        $('#conv-sidebar').removeClass('open');
    });

    // Delete conversation
    $(document).on('click', '.conv-delete', function(e) {
        e.stopPropagation();
        var id = $(this).data('id');
        if (!confirm('Eliminar esta conversacion?')) return;

        $.ajax({
            url: BASE + 'deleteConversation',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(data) {
                if (!data.success) return;
                // If deleted was active, reset
                if (currentConvId == id) {
                    startNewConversation();
                }
                loadConversations(currentConvId);
            }
        });
    });

    // Mobile sidebar toggle
    $(document).on('click', '#btn-toggle-sidebar', function() {
        $('#conv-sidebar').toggleClass('open');
    });
    $(document).on('click', '#btn-close-sidebar', function() {
        $('#conv-sidebar').removeClass('open');
    });

    // ─── Init ───
    $(document).ready(function() {
        loadConversations();
        $('#chat-input').focus();
    });

})();
</script>

</body>
</html>
