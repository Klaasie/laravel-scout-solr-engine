<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace Scout\Solr\Engines;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Scout\Solr\Client;
use Scout\Solr\ClientInterface;
use Scout\Solr\Events\AfterSelect;
use Scout\Solr\Events\BeforeSelect;
use Solarium\Core\Query\Helper;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;

/**
 * @mixin Client
 */
class SolrEngine extends Engine
{
    private Helper $helper;

    public function __construct(
        private ClientInterface $client,
        private Repository $config,
        private Dispatcher $events
    ) {
        $this->helper = new Helper();
    }

    public function update($models): ?ResultInterface
    {
        if ($models->isEmpty()) {
            return null;
        }

        $update = $this->client->createUpdate();
        $documents = $models->map(static function (Model $model) use ($update) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return null;
            }

            // Solr's unique key must hold the scout key, which delete() and map() rely on.
            return $update->createDocument(
                array_merge($searchableData, $model->scoutMetadata(), ['id' => $model->getScoutKey()])
            );
        })->filter()->values()->all();

        $update->addDocuments($documents);
        $update->addCommit();

        return $this->client->update($update, $this->client->endpointFor($models->first()));
    }

    public function delete($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $delete = $this->client->createUpdate();
        $delete->addDeleteByIds(
            $models->map->getScoutKey()
                ->values()
                ->all()
        );
        $delete->addCommit();

        $this->client->update($delete, $this->client->endpointFor($models->first()));
    }

    public function search(Builder $builder): mixed
    {
        return $this->performSearch($builder, array_filter([
            'filters' => $this->filters($builder),
            'limit' => $builder->limit,
        ]));
    }

    public function paginate(Builder $builder, $perPage, $page): mixed
    {
        return $this->performSearch($builder, array_filter([
            'filters' => $this->filters($builder),
            'limit' => (int) $perPage,
            'offset' => ($page - 1) * $perPage,
        ]));
    }

    public function mapIds($results): SupportCollection
    {
        return $this->mapIdsFrom($results, 'id');
    }

    public function mapIdsFrom($results, $key): SupportCollection
    {
        return collect($results)->map(static function (Document $document) use ($key) {
            return $document->getFields()[$key] ?? null;
        })->values();
    }

    /**
     * @param Builder $builder
     * @param Result $results
     * @param Model $model
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results->getNumFound() === 0) {
            return $model->newCollection();
        }

        $objectIds = $this->mapIds($results->getDocuments())->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds($builder, $objectIds)
            ->filter(function ($model) use ($objectIdPositions) {
                return isset($objectIdPositions[$model->getScoutKey()]);
            })->sortBy(function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })->values();
    }

    /**
     * @param Builder $builder
     * @param Result $results
     * @param Model $model
     * @return LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if ($results->getNumFound() === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = $this->mapIds($results->getDocuments())->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds($builder, $objectIds)
            ->cursor()
            ->filter(function ($model) use ($objectIdPositions) {
                return isset($objectIdPositions[$model->getScoutKey()]);
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
        $query = $this->client->createUpdate();
        $query->addDeleteQuery('*:*');
        $query->addCommit();

        $this->client->update($query, $this->client->endpointFor($model));
    }

    public function createIndex($name, array $options = [])
    {
        return $this->client->createCore($name);
    }

    public function deleteIndex($name)
    {
        return $this->client->deleteCore($name);
    }

    protected function performSearch(Builder $builder, array $options = []): mixed
    {
        $endpoint = $this->client->endpointFor($builder->model);

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->client,
                $builder->query,
                $options + ['endpoint' => $endpoint]
            );
        }

        $query = $this->client->createSelect();
        $query->setQuery((string) $builder->query !== '' ? (string) $builder->query : '*:*');

        if (!empty($options['filters'])) {
            $query->createFilterQuery('scout')->setQuery($options['filters']);
        }

        foreach ($builder->orders as $order) {
            $query->addSort($order['column'], $order['direction']);
        }

        $query->setStart($options['offset'] ?? 0)
            ->setRows((int) ($options['limit'] ?? $this->config->get('scout-solr.select.limit', 10)));

        $this->events->dispatch(new BeforeSelect($query, $builder));

        $result = $this->client->select($query, $endpoint);

        $this->events->dispatch(new AfterSelect($result, $builder->model, $builder));

        return $result;
    }

    protected function filters(Builder $builder): string
    {
        $filters = collect($this->wheres($builder))->map(function (array $where) {
            return $this->whereClause(...$where);
        });

        foreach ($builder->whereIns as $field => $values) {
            // An empty whereIn can never match, mirroring Eloquent's behaviour.
            $filters->push(empty($values) ? '-*:*' : sprintf('%s:(%s)', $field, $this->implodeOr($values)));
        }

        foreach ($builder->whereNotIns as $field => $values) {
            if (!empty($values)) {
                $filters->push(sprintf('-%s:(%s)', $field, $this->implodeOr($values)));
            }
        }

        return $filters->values()->implode(' AND ');
    }

    /**
     * Scout 10 stores wheres as [field => value]; Scout 11 as a list of
     * ['field' => ..., 'operator' => ..., 'value' => ...] entries.
     *
     * @return list<array{0: string, 1: string, 2: mixed}>
     */
    private function wheres(Builder $builder): array
    {
        $wheres = [];

        foreach ($builder->wheres as $key => $where) {
            if (is_array($where) && array_key_exists('field', $where)) {
                $wheres[] = [(string) $where['field'], (string) ($where['operator'] ?? '='), $where['value'] ?? null];
                continue;
            }

            $wheres[] = [(string) $key, '=', $where];
        }

        return $wheres;
    }

    private function whereClause(string $field, string $operator, mixed $value): string
    {
        if ($value === null) {
            // Matches documents without (or, negated, with) a value for the field.
            return match ($operator) {
                '=', '==' => sprintf('-%s:[* TO *]', $field),
                '!=', '<>' => sprintf('%s:[* TO *]', $field),
                default => throw new InvalidArgumentException(
                    sprintf('Operator "%s" cannot be used with a null value on "%s".', $operator, $field)
                ),
            };
        }

        $value = $this->filterValue($value);

        return match ($operator) {
            '=', '==' => sprintf('%s:%s', $field, $value),
            '!=', '<>' => sprintf('-%s:%s', $field, $value),
            '>' => sprintf('%s:{%s TO *]', $field, $value),
            '>=' => sprintf('%s:[%s TO *]', $field, $value),
            '<' => sprintf('%s:[* TO %s}', $field, $value),
            '<=' => sprintf('%s:[* TO %s]', $field, $value),
            default => throw new InvalidArgumentException(
                sprintf('Operator "%s" is not supported by the Solr engine.', $operator)
            ),
        };
    }

    private function implodeOr(array $values): string
    {
        return collect($values)->map(fn ($value) => $this->filterValue($value))->values()->implode(' OR ');
    }

    /**
     * Quote string filter values so spaces and query syntax are matched literally.
     * Numbers are left as they are, booleans become true/false and dates use Solr's format.
     */
    private function filterValue(mixed $value): string
    {
        if (!$this->config->get('scout-solr.select.escape_filter_values', true)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $this->helper->escapePhrase((string) $this->helper->formatDate($value));
        }

        return $this->helper->escapePhrase((string) $value);
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
