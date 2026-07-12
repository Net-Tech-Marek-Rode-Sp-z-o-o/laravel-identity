<?php

declare(strict_types=1);

return [

    'route_prefix' => 'auth',

    'register_enabled' => true,

    'token_name' => 'api',

    'password_reset_ttl' => 60,

    'invitation_ttl' => 4320,

    'two_factor' => [

        'issuer' => env('IDENTITY_2FA_ISSUER'),

        'challenge_ttl' => 5,

    ],

];
