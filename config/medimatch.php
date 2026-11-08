<?php

return [
    /*
    | Development-mode account verification.
    | No SMS provider is configured and email uses the "log" mailer, so in local
    | development the one-time code is shown on screen (clearly labelled) and
    | written to storage/logs/laravel.log. Never enable this in production.
    */
    'show_dev_codes' => (bool) env('MEDIMATCH_SHOW_DEV_CODES', env('APP_ENV') === 'local'),

    'verification_code_ttl_minutes' => 10,
    'verification_max_attempts' => 5,
];
