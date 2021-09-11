# Laravel Scout Apache Solr driver
This package provides a basic implementation of the Apache Solr search engine within Laravel Scout.

---

## Installation

---

`composer install klaasie/scout-solr-engine`

## config

---

This package provides a config file that can be modified using .env variables.
You can initialize your own config file with: 

`sail artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"`

### scout:index

By default, Solr doesn't allow indexes (cores) to be created without providing the proper folders and files on the file system first.
However, if a default config set is set up in the Solr instance this becomes possible through the API.
The `scout:index` command will only work if the Solr instance is properly configured and the config files has the corresponding name for the config set folder.
For more information, see [https://solr.apache.org/guide/8_9/config-sets.html#config-sets](https://solr.apache.org/guide/8_9/config-sets.html#config-sets)

### Cores (indexes)

Within the config file a core (index) is not provided. The engine will determine which core to connect to using the `searchableAs()` method on the model.

## Example

---

This repository provides an example laravel app showcasing the functionality of this package.
Please refer to the [README.md](example/README.md) of the example app for information on how to get it started.