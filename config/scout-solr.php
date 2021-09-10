<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Solr Admin config
    |--------------------------------------------------------------------------
    |
    | Set a default ConfigSet used for the createIndex command.
    | The ConfigSet is required for the command to work.
    |
    |
    */
    'create' => [
        'config_set' => env('SOLR_CONFIG_SET', '_default'),
    ],
    'unload' => [
        'delete_index' => env('SOLR_UNLOAD_DELETE_INDEX', false),
        'delete_data_dir' => env('SOLR_UNLOAD_DELETE_DATA_DIR', false),
        'delete_instance_dir' => env('SOLR_UNLOAD_DELETE_INSTANCE_DIR', false),
    ],
    'select' => [
        'limit' => env('SOLR_SELECT_DEFAULT_LIMIT', 10),
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
            'endpoint' => [
                'localhost' => [
                    'host' => env('SOLR_HOST', 'localhost'),
                    'port' => env('SOLR_PORT', 8983),
                    'path' => env('SOLR_PATH', '/'),
                ],
            ],
        ],
    ],
];