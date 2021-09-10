<?php

namespace Scout\Solr\Engines;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Scout\Solr\ClientInterface;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;

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

    public function search(Builder $builder): Result
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

    /**
     * @param Builder $builder
     * @param Result $results
     * @param Model $model
     * @return Collection|void
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results->getNumFound() === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results->getDocuments())->map(static function (Document $document) {
            return $document->getFields()['id'];
        })->values()->all();

        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds($builder, $objectIds)
            ->filter(function ($model) use ($objectIds) {
                return in_array($model->getScoutKey(), $objectIds, false);
            })->sortBy(function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })->values();
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

    protected function performSearch(Builder $builder, array $options = []): Result
    {
        $query = $this->client->setCore($builder->model)->createSelect();
        $query->setQuery($builder->query);
        $query->setStart(0)->setRows($options['limit'] ?? $this->config->get('scout-solr.select.limit'));

        return $this->client->select($query);
    }
}
