<?php

declare(strict_types=1);

use Scout\Solr\ClientInterface;

return [
    /*
    |--------------------------------------------------------------------------
    | Solr Cloud
    |--------------------------------------------------------------------------
    |
    | Different Solr APIs are used depending on whether standalone or cloud is being used.
    |
    |
    */
    'cloud' => env('SOLR_CLOUD', false),

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
            'config_set' => env('SOLR_CONFIG_SET', '_default'),
            // Core is set through searchableAs()

            /*
             * These settings are used with SOLR cloud.
             * When using router_name "compositeId" the num_shards config is required.
             * When using router_name "implicit" the shards config is required.
             */
            'router_name' => env('SOLR_ROUTER_NAME', ClientInterface::ROUTER_NAME_COMPOSITE_ID),
            'num_shards' => env('SOLR_NUM_SHARDS', 1),
            'shards' => env('SOLR_SHARDS', 'shard-x,shard-y,shard-z'),
        ],
        // Example of a core defined through config
//        'books' => [
//            'host' => env('SOLR_HOST', 'solr2'),
//            'port' => env('SOLR_PORT', 8983),
//            'path' => env('SOLR_PATH', '/'),
//            'config_set' => env('SOLR_CONFIG_SET', '_default'),
//            'core' => env('SOLR_CORE', 'books'),

//            'router_name' => env('SOLR_ROUTER_NAME', ClientInterface::ROUTER_NAME_COMPOSITE_ID'),
//            'num_shards' => env('SOLR_NUM_SHARDS', 1),
//            'shards' => env('SOLR_SHARDS', 1),
//        ],
    ],
];
