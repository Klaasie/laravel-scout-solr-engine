<?php

namespace Scout\Solr\Engines;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Scout\Solr\ClientInterface;

class SolrEngine extends Engine
{
    private ClientInterface $client;
    private Repository $config;

    public function __construct(ClientInterface $client, Repository $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function update($models)
    {
        $this->client->setCore($models->first());

        $update = $this->client->createUpdate();
        $documents = $models->map(static function (Model $model) use ($update) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            return $update->createDocument(
                array_merge($searchableData, $model->scoutMetadata())
            );
        })->filter()->values()->all();

        $update->addDocuments($documents);
        $update->addCommit();

        return $this->client->update($update);
    }

    public function delete($models)
    {
        // TODO: Implement delete() method.
    }

    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
//            'filters' => $this->filters($builder),
            'limit' => $builder->limit,
        ]));
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        // TODO: Implement paginate() method.
    }

    public function mapIds($results)
    {
        // TODO: Implement mapIds() method.
    }

    public function map(Builder $builder, $results, $model)
    {
        // TODO: Implement map() method.
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
        // TODO: Implement lazyMap() method.
    }

    public function getTotalCount($results)
    {
        // TODO: Implement getTotalCount() method.
    }

    public function flush($model): void
    {
        $query = $this->client->setCore(new $model())->createUpdate();
        $query->addDeleteQuery('*:*');
        $query->addCommit();

        $this->client->update($query);
    }

    public function createIndex($name, array $options = [])
    {
        $coreAdminQuery = $this->client->createCoreAdmin();

        $action = $coreAdminQuery->createCreate();
        $action->setCore($name);
        $action->setConfigSet($this->config->get('scout-solr.create.config_set'));

        $coreAdminQuery->setAction($action);
        return $this->client->coreAdmin($coreAdminQuery);
    }

    public function deleteIndex($name)
    {
        $coreAdminQuery = $this->client->createCoreAdmin();

        $action = $coreAdminQuery->createUnload();
        $action->setCore($name);
        $action->setDeleteIndex($this->config->get('scout-solr.unload.delete_index'));
        $action->setDeleteDataDir($this->config->get('scout-solr.unload.delete_data_dir'));
        $action->setDeleteInstanceDir($this->config->get('scout-solr.unload.delete_instance_dir'));

        $coreAdminQuery->setAction($action);
        return $this->client->coreAdmin($coreAdminQuery);
    }

    protected function performSearch(Builder $builder, array $options = [])
    {
        $query = $this->client->setCore($builder->model)->createSelect();
        $query->setQuery($builder->query);
        $query->setStart(0)->setRows($options['limit'] ?? $this->config->get('scout-solr.select.limit'));

        $result = $this->client->select($query);
        dd($result, $builder);

        return $result;
    }
}
