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

    /*
    | Medimatch BD semantic matching service (ai-service/, Python FastAPI).
    | Only medicine descriptions are ever sent — never names, contact details or notes.
    | identity_threshold / identity_margin come from ai-service/evaluation (see its README).
    */
    'ai' => [
        'url' => env('AI_SERVICE_URL'),
        'key' => env('AI_SERVICE_KEY'),
        'timeout' => (float) env('AI_SERVICE_TIMEOUT', 3),
        'identity_threshold' => (float) env('AI_IDENTITY_THRESHOLD', 0.86),
        'identity_margin' => (float) env('AI_IDENTITY_MARGIN', 0.015),
        'retry_after_seconds' => 60, // after a failure, use keyword matching for this long before trying again
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
