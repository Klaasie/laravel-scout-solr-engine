<?php

declare(strict_types=1);

namespace Scout\Solr;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Scout\EngineManager;
use Scout\Solr\Engines\SolrEngine;
use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Adapter\ConnectionTimeoutAwareInterface;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Adapter\Http;
use Solarium\Core\Client\Adapter\TimeoutAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ScoutSolrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/scout-solr.php', 'scout-solr');

        $this->app->singleton(ClientInterface::class, static function (Application $app) {
            $config = $app->make(Repository::class);

            $client = new Client(
                self::makeAdapter($app, $config),
                new EventDispatcher(),
                $config,
            );

            // The default endpoint is only used when the client is called directly
            // without an endpoint; the engine always passes the model's own endpoint.
            $client->getEndpoint()->setOptions((array) $config->get('scout-solr.endpoints.default', []));

            return $client;
        });

        $this->app->alias(ClientInterface::class, Client::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Laravel 13 binds custom driver callbacks to the manager, so `$this` can't be
        // relied on inside the callback; capture the container instead.
        $app = $this->app;
        $app->make(EngineManager::class)->extend('solr', fn () => $app->make(SolrEngine::class));

        $this->publishes([
            __DIR__ . '/../config/scout-solr.php' => config_path('scout-solr.php'),
        ], 'scout-solr-config');
    }

    /**
     * An adapter bound in the container (e.g. Solarium's Psr18Adapter) wins; otherwise
     * the configured built-in adapter is created with the configured timeouts.
     */
    private static function makeAdapter(Application $app, Repository $config): AdapterInterface
    {
        if ($app->bound(AdapterInterface::class)) {
            return $app->make(AdapterInterface::class);
        }

        $name = (string) $config->get('scout-solr.http.adapter', 'curl');

        $adapter = match ($name) {
            'curl' => new Curl(),
            'http' => new Http(),
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported Solr HTTP adapter "%s". Use "curl" or "http", or bind %s in the container.',
                $name,
                AdapterInterface::class
            )),
        };

        $timeout = $config->get('scout-solr.http.timeout');
        if ($adapter instanceof TimeoutAwareInterface && $timeout !== null && $timeout !== '') {
            $adapter->setTimeout((int) $timeout);
        }

        $connectionTimeout = $config->get('scout-solr.http.connection_timeout');
        $hasConnectionTimeout = $connectionTimeout !== null && $connectionTimeout !== '';
        if ($adapter instanceof ConnectionTimeoutAwareInterface && $hasConnectionTimeout) {
            $adapter->setConnectionTimeout((int) $connectionTimeout);
        }

        return $adapter;
    }
}
