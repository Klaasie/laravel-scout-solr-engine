<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace Scout\Solr\Engines;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Scout\Solr\Client;
use Scout\Solr\ClientInterface;
use Scout\Solr\Events\AfterSelect;
use Scout\Solr\Events\BeforeSelect;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;

/**
 * @mixin Client
 */
class SolrEngine extends Engine
{
    private ClientInterface $client;
    private Repository $config;
    private Dispatcher $events;

    public function __construct(
        ClientInterface $client,
        Repository $config,
        Dispatcher $events
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->events = $events;
    }

    public function update($models): ResultInterface
    {
        $this->client->setCore($models->first());

        $update = $this->client->createUpdate();
        $documents = $models->map(static function (Model $model) use ($update) {
            if (empty($searchableData = $model->toSearchableArray())) {
                /** @noinspection PhpInconsistentReturnPointsInspection */
                return;
            }

            return $update->createDocument(
                array_merge($searchableData, $model->scoutMetadata())
            );
        })->filter()->values()->all();

        $update->addDocuments($documents);
        $update->addCommit();

        return $this->client->update($update, $this->getEndpointFromConfig($models->first()->searchableAs()));
    }

    public function delete($models): void
    {
        $this->client->setCore($models->first());

        $delete = $this->client->createUpdate();
        $delete->addDeleteByIds(
            $models->map->getScoutKey()
                ->values()
                ->all()
        );
        $delete->addCommit();

        $this->client->update($delete, $this->getEndpointFromConfig($models->first()->searchableAs()));
    }

    public function search(Builder $builder): Result
    {
        return $this->performSearch($builder, array_filter([
            'filters' => $this->filters($builder),
            'limit' => $builder->limit,
        ]));
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, array_filter([
            'filters' => $this->filters($builder),
            'limit' => (int) $perPage,
            'offset' => ($page - 1) * $perPage,
        ]));
    }

    public function mapIds($results): SupportCollection
    {
        return collect($results)->map(static function (Document $document) {
            return $document->getFields()['id'];
        })->values();
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
                return in_array($model->getScoutKey(), $objectIds);
            })->sortBy(function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })->values();
    }

    /**
     * @param Builder $builder
     * @param Result $results
     * @param Model $model
     * @return LazyCollection|void
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if ($results->getNumFound() === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = collect($results->getDocuments())->map(static function (Document $document) {
            return $document->getFields()['id'];
        })->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds($builder, $objectIds)
            ->cursor()
            ->filter(function ($model) use ($objectIds) {
                return in_array($model->getScoutKey(), $objectIds);
            })->sortBy(function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })->values();
    }

    /**
     * @param Result $results
     * @return int
     */
    public function getTotalCount($results): int
    {
        return $results->getNumFound();
    }

    public function flush($model): void
    {
        $query = $this->client->setCore(new $model())->createUpdate();
        $query->addDeleteQuery('*:*');
        $query->addCommit();

        $this->client->update($query, $this->getEndpointFromConfig($model->searchableAs()));
    }

    public function createIndex($name, array $options = [])
    {
        return $this->client->createCore($name);
    }

    public function deleteIndex($name)
    {
        return $this->client->deleteCore($name);
    }

    protected function performSearch(Builder $builder, array $options = []): Result
    {
        $this->client->setCore($builder->model);

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->client,
                $builder->query,
                $options
            );
        }

        $query = $this->client->createSelect();
        if (array_key_exists('filters', $options)) {
            $query->setQuery($options['filters']);
        } else {
            $query->setQuery($builder->query);
        }

        foreach ($builder->orders as $order) {
            $query->addSort($order['column'], $order['direction']);
        }

        $query->setStart($options['offset'] ?? 0)
            ->setRows($options['limit'] ?? $this->config->get('scout-solr.select.limit'));

        $this->events->dispatch(new BeforeSelect($query, $builder));

        $result = $this->client->select($query, $this->getEndpointFromConfig($builder->model->searchableAs()));

        $this->events->dispatch(new AfterSelect($result, $builder->model));

        return $result;
    }

    protected function filters(Builder $builder): string
    {
        $filters = collect($builder->wheres)->map(function ($value, $key) {
            return sprintf('%s:%s', $key, $value);
        });

        foreach ($builder->whereIns as $key => $values) {
            $filters->push(sprintf('%s:(%s)', $key, collect($values)->map(function ($value) {
                return $value;
            })->values()->implode(' OR ')));
        }

        return $filters->values()->implode(' AND ');
    }



    /**
     * Dynamically call the Solr client instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->client->$method(...$parameters);
    }
}
