# Laravel Scout Apache Solr driver
[![GitHub issues](https://img.shields.io/github/issues/Klaasie/laravel-scout-solr-engine)](https://github.com/Klaasie/laravel-scout-solr-engine/issues)
[![Latest Stable Version](http://poser.pugx.org/klaasie/scout-solr-engine/v)](https://packagist.org/packages/klaasie/scout-solr-engine)
[![License](http://poser.pugx.org/klaasie/scout-solr-engine/license)](https://packagist.org/packages/klaasie/scout-solr-engine) 
[![PHP Version Require](http://poser.pugx.org/klaasie/scout-solr-engine/require/php)](https://packagist.org/packages/klaasie/scout-solr-engine)

This package provides a basic implementation of the Apache Solr search engine within Laravel Scout.

## Installation

`composer require klaasie/scout-solr-engine`

## config

This package provides a config file that can be modified using .env variables.  
You can initialize your own config file with: 

`php artisan vendor:publish --provider="Scout\Solr\ScoutSolrServiceProvider"`

### scout:index

By default, Solr doesn't allow indexes (cores) to be created without providing the proper folders and files on the file system first.
However, if a default config set is set up in the Solr instance this becomes possible through the API.  
The `scout:index` command will only work if the Solr instance is properly configured and the config files has the corresponding name for the config set folder.
For more information, see [https://solr.apache.org/guide/8_9/config-sets.html#config-sets](https://solr.apache.org/guide/8_9/config-sets.html#config-sets)

### Cores (indexes)

Within the config file a core (index) is not provided. The engine will determine which core to connect to using the `searchableAs()` method on the model.

Alternatively, if a specific model is on a different Solr instance, another configuration can be provided for this model.
It's important for the configuration key to match the `searchableAs()` of the model.

## [Solarium](https://github.com/solariumphp/solarium)

This package uses [solarium/solarium](https://github.com/solariumphp/solarium) to handle requests to the solr instance.
This app is meant to be a simple implementation of the laravel/scout engine. For complex queries to the solr instance I would recommend initializing your own Solarium client and use that package.
Visit [https://solarium.readthedocs.io/en/stable/](https://solarium.readthedocs.io/en/stable/) to view the documentation of the solarium package.

For convenience, any unknown methods used on the engine will be forwarded to the solarium client.

```php
$model = new \App\Models\SearchableModel();

/** @var \Scout\Solr\Engines\SolrEngine $engine */
$engine = app(\Laravel\Scout\EngineManager::class)->engine();
$select = $engine->setCore($model)->createSelect();
$select->setQuery('*:*');
$result = $engine->select($select, $engine->getEndpointFromConfig($model->searchableAs())); // getEndpointFromConfig() is only necessary when your model does not use the default solr instance.
```

## Events
The Solr Engine dispatches several events allowing you to hook into specific points in the engine.

| Event | Usage |
|---------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
|Scout\Solr\Events\BeforeSelect|Contains the Solr `Solarium\QueryType\Select\Query\Query` object and Scout `Builder` object. This event allows you to create complex queries using the Solarium package.|
|Scout\Solr\Events\BeforeSelect|Contains the Solr `Solarium\QueryType\Select\Result\Result` object and `Model` object. This event allows you to create complex queries using the Solarium package.|


## Example

This repository provides an example laravel app showcasing the functionality of this package.   
Please refer to the [README.md](example/README.md) of the example app for information on how to get it started.
