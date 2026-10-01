<?php

declare(strict_types=1);

return [

    'route_prefix' => 'auth',

    'register_enabled' => true,

    'token_name' => 'api',

    'password_reset_ttl' => 60,

    'email_verification_ttl' => 1440,

    'invitation_ttl' => 4320,

    'throttle' => [

        'login_per_minute' => 5,

        'login_per_minute_per_ip' => 20,

        'two_factor_per_minute' => 5,

        'register_per_minute' => 5,

        'password_per_minute' => 5,

        'password_requests_per_hour_per_email' => 3,

        'password_requests_per_hour_per_ip' => 20,

        'tokens_per_minute' => 10,

        'mail_per_hour' => 10,

    ],

    'two_factor' => [

        'issuer' => env('IDENTITY_2FA_ISSUER'),

        'challenge_ttl' => 5,

    ],

];
