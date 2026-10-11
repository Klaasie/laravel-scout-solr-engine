<?php

declare(strict_types=1);

namespace Scout\Solr;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scout\Solr\Exceptions\InvalidRouterNameSupplied;
use Solarium\Client as ClientBase;
use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Query\Result\ResultInterface;

class Client extends ClientBase implements ClientInterface
{
    private Repository $config;

    /**
     * Endpoints resolved per searchableAs() name, so each model talks to its own
     * core without changing shared client state.
     *
     * @var array<string, Endpoint>
     */
    private array $resolvedEndpoints = [];

    /**
     * @param array<string, mixed>|null $options
     */
    public function __construct(
        AdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        Repository $config,
        ?array $options = null
    ) {
        parent::__construct($adapter, $eventDispatcher, $options);
        $this->config = $config;
    }

    public function endpointFor(Model|string $model): Endpoint
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $name = $model instanceof Model ? (string) $model->searchableAs() : $model;

        if (!isset($this->resolvedEndpoints[$name])) {
            $options = $this->endpointConfig($name);
            $options['core'] ??= $name;

            $this->resolvedEndpoints[$name] = new Endpoint($options);
        }

        return $this->resolvedEndpoints[$name];
    }

    public function createCore(string $name): ResultInterface
    {
        $key = $this->endpointKey($name);

        if ($this->config->get('scout-solr.cloud')) {
            $routerName = $this->config->get('scout-solr.endpoints.' . $key . '.router_name');

            $collectionAdminQuery = $this->createCollections();

            $action = $collectionAdminQuery->createCreate();
            $action->setName($name);
            $action->setRouterName($routerName);

            match ($routerName) {
                ClientInterface::ROUTER_NAME_COMPOSITE_ID => $action->setNumShards(
                    (int) $this->config->get('scout-solr.endpoints.' . $key . '.num_shards')
                ),
                ClientInterface::ROUTER_NAME_IMPLICIT => $action->setShards(
                    $this->config->get('scout-solr.endpoints.' . $key . '.shards')
                ),
                default => throw InvalidRouterNameSupplied::forRouterName((string) $routerName),
            };

            $collectionAdminQuery->setAction($action);

            return $this->collections($collectionAdminQuery, $this->adminEndpoint($name));
        }

        $coreAdminQuery = $this->createCoreAdmin();
        $action = $coreAdminQuery->createCreate();
        $action->setCore($name);
        $action->setConfigSet($this->config->get('scout-solr.endpoints.' . $key . '.config_set', '_default'));
        $coreAdminQuery->setAction($action);

        return $this->coreAdmin($coreAdminQuery, $this->adminEndpoint($name));
    }

    public function deleteCore(string $name): ResultInterface
    {
        if ($this->config->get('scout-solr.cloud')) {
            $collectionAdminQuery = $this->createCollections();

            $action = $collectionAdminQuery->createDelete();
            $action->setName($name);
            $collectionAdminQuery->setAction($action);

            return $this->collections($collectionAdminQuery, $this->adminEndpoint($name));
        }

        $coreAdminQuery = $this->createCoreAdmin();

        $action = $coreAdminQuery->createUnload();
        $action->setCore($name);
        $action->setDeleteIndex((bool) $this->config->get('scout-solr.unload.delete_index'));
        $action->setDeleteDataDir((bool) $this->config->get('scout-solr.unload.delete_data_dir'));
        $action->setDeleteInstanceDir((bool) $this->config->get('scout-solr.unload.delete_instance_dir'));
        $coreAdminQuery->setAction($action);

        return $this->coreAdmin($coreAdminQuery, $this->adminEndpoint($name));
    }

    /**
     * The Solr instance a core lives on, without a core set: used for admin requests.
     */
    private function adminEndpoint(string $name): Endpoint
    {
        $options = $this->endpointConfig($name);
        unset($options['core'], $options['collection']);

        return new Endpoint($options);
    }

    /**
     * A core uses its own endpoint config when one is keyed by its name, and the default one otherwise.
     */
    private function endpointKey(string $name): string
    {
        return $this->config->has('scout-solr.endpoints.' . $name) ? $name : 'default';
    }

    /**
     * @return array<string, mixed>
     */
    private function endpointConfig(string $name): array
    {
        return (array) $this->config->get('scout-solr.endpoints.' . $this->endpointKey($name), []);
    }
}
