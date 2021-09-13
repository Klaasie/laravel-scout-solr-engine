<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Solr Admin ConfigSet
    |--------------------------------------------------------------------------
    |
    | Set a default ConfigSet used for the createIndex command.
    | Without a ConfigSet Solr won't be able to create indexes through the command.
    |
    |
    */
    'create' => [
        'config_set' => env('SOLR_CONFIG_SET', '_default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Solr Admin unload
    |--------------------------------------------------------------------------
    |
    | Settings used for the deleteIndex command.
    |
    |
    */
    'unload' => [
        'delete_index' => env('SOLR_UNLOAD_DELETE_INDEX', false),
        'delete_data_dir' => env('SOLR_UNLOAD_DELETE_DATA_DIR', false),
        'delete_instance_dir' => env('SOLR_UNLOAD_DELETE_INSTANCE_DIR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Solr Select
    |--------------------------------------------------------------------------
    |
    | Solr does not allow for unlimited results. By default, Solr limits queries to 10 results.
    | When a limit is not provided through the builder instance this config is used instead.
    |
    |
    */
    'select' => [
        'limit' => env('SOLR_SELECT_DEFAULT_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Solr Endpoint
    |--------------------------------------------------------------------------
    |
    | Set the default Solr instance for the client to work with.
    | A core is not necessary here, the client will set the right core based on the model searchableAs() value.
    |
    | If a model is stored on a different instance of Solr additional configs can be provided here.
    | It is important that the key of the configuration is equal to the models searchableAs()
    |
    |
    */
    'endpoints' => [
        'default' => [
            'host' => env('SOLR_HOST', 'localhost'),
            'port' => env('SOLR_PORT', 8983),
            'path' => env('SOLR_PATH', '/'),
            // Core is set through searchableAs()
        ],
        // Example of a core defined through config
//        'books' => [
//            'host' => env('SOLR_HOST', 'solr2'),
//            'port' => env('SOLR_PORT', 8983),
//            'path' => env('SOLR_PATH', '/'),
//            'core' => env('SOLR_CORE', 'books'),
//        ],
    ],
];
