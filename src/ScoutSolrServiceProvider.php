<?php

namespace Scout\Solr;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Scout\Solr\Engines\SolrEngine;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ScoutSolrServiceProvider extends ServiceProvider
{
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

    public function register(): void
    {
        $this->app->bind(\Solarium\Client::class, static function (Application $app) {
            return new \Solarium\Client(
                new Curl(),
                new EventDispatcher(),
                $app['config']->get('scout-solr.endpoints')
            );
        });
    }
}