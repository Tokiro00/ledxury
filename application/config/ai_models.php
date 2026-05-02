<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Configuración centralizada de modelos de AI.
 *
 * Antes los modelos estaban hardcoded en Aiassistant.php y Agents.php.
 * Cuando salía una nueva versión (Sonnet 5, etc.) había que tocar código.
 * Ahora basta editar este archivo (o sobrescribir en `secrets.php`).
 *
 * Los API keys siguen viviendo en `secrets.php` (gitignored).
 *
 * Uso desde un controller:
 *   $models = $this->config->item('ai_models');
 *   $payload['model'] = $models['anthropic']['default'];
 */

$config['ai_models'] = array(

    // Anthropic Claude — provider primario para AI assistant + agents.
    // 'default' = el que usa Aiassistant.php para chat normal.
    // 'fast'    = el que usa Agents.php para batch (collections messages).
    // Cuando cambies aquí, automáticamente lo toman ambos controllers.
    'anthropic' => array(
        'default' => 'claude-sonnet-4-20250514',
        'fast'    => 'claude-haiku-4-5-20251001',
        'api_url' => 'https://api.anthropic.com/v1/messages',
        'version' => '2023-06-01',
        'max_tokens_default' => 1024,
        'max_tokens_long'    => 4096,
    ),

    // Groq — fallback rápido y barato cuando Anthropic falla.
    'groq' => array(
        'default' => 'llama-3.3-70b-versatile',
        'api_url' => 'https://api.groq.com/openai/v1/chat/completions',
        'max_tokens_default' => 1024,
    ),

    // Google Gemini — segundo fallback.
    'gemini' => array(
        'default' => 'gemini-2.0-flash',
        'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
    ),

    // Orden de fallback cuando Aiassistant llama tryProvidersInOrder().
    // Si Anthropic falla → Groq → Gemini.
    'fallback_order' => array('anthropic', 'groq', 'gemini'),
);
