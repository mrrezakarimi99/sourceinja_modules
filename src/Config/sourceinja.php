<?php

return [
    'prefix_cache' => 'sourceinja_',

    'default_service' => env('SOURCEINJA_DEFAULT_SERVICE', 'github'),

    'services' => [
        'gitlab' => [
            'api_url' => env('GITLAB_API_URL'),
            'api_key' => env('GITLAB_API_KEY'),
        ],
        'github' => [
            'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
            'api_key' => env('GITHUB_API_KEY'),
            'username' => env('GITHUB_USERNAME', 'mrrezakarimi99'),  // For personal account
            'organization' => env('GITHUB_ORGANIZATION'),  // Optional for organization accounts
        ],
    ],
];