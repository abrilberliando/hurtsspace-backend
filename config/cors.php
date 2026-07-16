<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://hurtsspace.com',
        'https://www.hurtsspace.com',
        'https://*.hurtsspace.com',
        'http://localhost:3000',
        'https://hurtsspace-frontend-staging.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
