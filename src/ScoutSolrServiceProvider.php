<?php

declare(strict_types=1);

namespace Scout\Solr;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Scout\Solr\Engines\SolrEngine;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Endpoint;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ScoutSolrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scout-solr.php', 'scout-solr');

        $this->app->singleton(ClientInterface::class, static function (Application $app) {
            $client = new Client(
                new Curl(),
                new EventDispatcher(),
                null,
            );

            $client->getEndpoint('localhost')->setOptions($app['config']->get('scout-solr.endpoints.default'));
            return $client;
        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->app->make(EngineManager::class)->extend('solr', function () {
            return $this->app->make(SolrEngine::class);
        });

        $this->publishes([
            __DIR__ . '/../config/scout-solr.php' => config_path('scout-solr.php'),
        ]);
    }
}
