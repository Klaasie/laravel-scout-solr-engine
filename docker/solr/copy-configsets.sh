#!/bin/bash
# The official Solr image keeps its config sets under /opt/solr, outside the Solr
# home. Copy them in so cores can be created from `_default` through the API,
# which is what `php artisan scout:index` does.
cp -rn /opt/solr/server/solr/configsets /var/solr/data/
