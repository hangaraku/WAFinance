<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
    ],

    'google' => [
        // Google Generative Language API key (do NOT commit real keys)
        'generative_api_key' => env('GENERATIVE_LANGUAGE_API_KEY'),
        // Optional model name for the Generative API. Example: 'gemini-2.0-flash' or 'gemini-2.1'
        'model' => env('GENERATIVE_LANGUAGE_MODEL', 'gemini-2.0-flash'),
    ],

    'messaging' => [
        // Webhook secret for validating incoming webhook requests (HMAC SHA256)
        'webhook_secret' => env('MESSAGING_WEBHOOK_SECRET'),
        // Verification token for webhook setup (e.g., WhatsApp Business API)
        'verify_token' => env('MESSAGING_VERIFY_TOKEN'),
    ],

    'openai' => [
        'api_key' => env('OPEN_AI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5-nano'),
    ],

    // AI Provider selection: 'gemini' or 'openai'
    'ai' => [
        'provider' => env('AI_PROVIDER', 'openai'),
    ],

];
