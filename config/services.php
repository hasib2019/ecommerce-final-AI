<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    // Optional private-subdomain gate (e.g. an ERP at erp.your-domain.com). Off unless
    // ERP_GATE_SECRET is set: /dhaka then sets this cookie and the subdomain's .htaccess checks it.
    'erp' => [
        'url' => env('ERP_URL'),
        'secret' => env('ERP_GATE_SECRET'),
        'cookie_domain' => env('ERP_COOKIE_DOMAIN'),
        'minutes' => (int) env('ERP_GATE_MINUTES', 60),
    ],

];
