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

    public function __construct(
        AdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        Repository $config,
        array $options = null
    ) {
        parent::__construct($adapter, $eventDispatcher, $options);
        $this->config = $config;
    }


    public function setCore(Model $model): self
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $searchableAs = $model->searchableAs();

        if (is_array($searchableAs)) {
            return $this->addEndpoint($searchableAs);
        }

        $this->getEndpoint()->setCore($searchableAs);
        return $this;
    }

    public function createCore(string $name): ResultInterface
    {
        if ($this->config->get('scout-solr.cloud')) {
            $routerName = $this->config->get('scout-solr.endpoints.' . $name . '.router_name');

            $collectionAdminQuery = $this->createCollections();

            $action = $collectionAdminQuery->createCreate();
            $action->setName($name);
            $action->setRouterName($routerName);

            match ($routerName) {
                ClientInterface::ROUTER_NAME_COMPOSITE_ID => $action->setNumShards(
                    $this->config->get('scout-solr.endpoints.' . $name . '.num_shards')
                ),
                ClientInterface::ROUTER_NAME_IMPLICIT => $action->setShards(
                    $this->config->get('scout-solr.endpoints.' . $name . '.shards')
                ),
                default => InvalidRouterNameSupplied::forRouterName($routerName),
            };

            $collectionAdminQuery->setAction($action);

            return $this->collections($collectionAdminQuery, $this->getEndpointFromConfig($name));
        }

        $coreAdminQuery = $this->createCoreAdmin();
        $action = $coreAdminQuery->createCreate();
        $action->setCore($name);
        $action->setConfigSet($this->config->get('scout-solr.endpoints.' . $name . '.config_set', '_default'));
        $coreAdminQuery->setAction($action);

        return $this->coreAdmin($coreAdminQuery, $this->getEndpointFromConfig($name));
    }

    public function deleteCore(string $name): ResultInterface
    {
        if ($this->config->get('scout-solr.cloud')) {
            $collectionAdminQuery = $this->createCollections();

            $action = $collectionAdminQuery->createDelete();
            $action->setName($name);
            $collectionAdminQuery->setAction($action);

            return $this->collections($collectionAdminQuery, $this->getEndpointFromConfig($name));
        }

        $coreAdminQuery = $this->createCoreAdmin();

        $action = $coreAdminQuery->createUnload();
        $action->setCore($name);
        $action->setDeleteIndex($this->config->get('scout-solr.unload.delete_index'));
        $action->setDeleteDataDir($this->config->get('scout-solr.unload.delete_data_dir'));
        $action->setDeleteInstanceDir($this->config->get('scout-solr.unload.delete_instance_dir'));
        $coreAdminQuery->setAction($action);

        return $this->coreAdmin($coreAdminQuery, $this->getEndpointFromConfig($name));
    }

    public function getEndpointFromConfig(string $name): ?Endpoint
    {
        if ($this->config->get('scout-solr.endpoints.' . $name) === null) {
            return null;
        }

        return new Endpoint($this->config->get('scout-solr.endpoints.' . $name));
    }
}
