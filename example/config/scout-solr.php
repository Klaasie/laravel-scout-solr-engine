<?php

return [

    'create' => [
        'config_set' => env('SOLR_CONFIG_SET', '_default'),
    ],
    'unload' => [
        'delete_index' => env('SOLR_UNLOAD_DELETE_INDEX', true),
        'delete_data_dir' => env('SOLR_UNLOAD_DELETE_DATA_DIR', true),
        'delete_instance_dir' => env('SOLR_UNLOAD_DELETE_INSTANCE_DIR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Solr Endpoint
    |--------------------------------------------------------------------------
    |
    | @todo documentation
    |
    |
    */
    'endpoints' => [
        'default' => [
            'host' => env('SOLR_HOST', 'solr'),
            'port' => env('SOLR_PORT', 8983),
            'path' => env('SOLR_PATH', '/'),
            // Core is set through searchableAs()
        ],
        // Example of a core defined through config
        'books' => [
            'host' => env('SOLR_HOST', 'solr2'),
            'port' => env('SOLR_PORT', 8983),
            'path' => env('SOLR_PATH', '/'),
            'core' => env('SOLR_CORE', 'books'),
        ],
    ],
];
