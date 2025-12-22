<?php

return [
    'generate' => [
        'route' => [
            'actions' => env('WAYFINDER_GENERATE_ROUTE_ACTIONS', true),
            'named' => env('WAYFINDER_GENERATE_NAMED_ROUTES', true),
            'form_variant' => env('WAYFINDER_GENERATE_FORM_VARIANT', true),
            'ignore' => [
                // Patterns to ignore for URLs (e.g. 'nova-api/*')
                'urls' => [],
                // Patterns to ignore for route names (e.g. 'nova.*')
                'names' => ['nova.*'],
            ],
        ],
        'models' => env('WAYFINDER_GENERATE_MODELS', true),
        'inertia' => [
            'shared_data' => env('WAYFINDER_GENERATE_INERTIA_SHARED_DATA', true),
        ],
        'broadcast' => [
            'channels' => env('WAYFINDER_GENERATE_BROADCAST_CHANNELS', true),
            'events' => env('WAYFINDER_GENERATE_BROADCAST_EVENTS', true),
        ],
        'environment_variables' => env('WAYFINDER_GENERATE_ENVIRONMENT_VARIABLES', true),
        'enums' => env('WAYFINDER_GENERATE_ENUMS', true),
    ],

    // Format the generated files
    'format' => [
        'enabled' => env('WAYFINDER_FORMAT_ENABLED', false),
    ],

    'cache' => [
        'enabled' => env('WAYFINDER_CACHE_ENABLED', true),
        'directory' => env('WAYFINDER_CACHE_DIRECTORY', storage_path('wayfinder-cache')),
    ],
];
