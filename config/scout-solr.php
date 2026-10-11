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

        /*
         * String values passed to where(), whereIn() and whereNotIn() are quoted so they
         * match literally, including spaces and characters like + - : ( ). Set this to
         * false to send them as raw Solr query syntax instead (the pre-4.0 behaviour).
         */
        'escape_filter_values' => env('SOLR_ESCAPE_FILTER_VALUES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    |
    | The Solarium adapter used to talk to Solr: "curl" (needs ext-curl) or "http"
    | (PHP streams). To use another adapter, such as Solarium's Psr18Adapter, bind
    | Solarium\Core\Client\Adapter\AdapterInterface in the container; these
    | settings are then ignored. Timeouts are in seconds.
    |
    */
    'http' => [
        'adapter' => env('SOLR_HTTP_ADAPTER', 'curl'),
        'timeout' => env('SOLR_TIMEOUT', 5),
        'connection_timeout' => env('SOLR_CONNECTION_TIMEOUT'),
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
    | It is important that the key of the configuration is equal to the models searchableAs().
    | The core defaults to that same name, so 'core' only needs setting when it differs.
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
