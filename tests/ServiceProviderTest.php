<?php

namespace Scout\Solr\Tests;

use InvalidArgumentException;
use Laravel\Scout\EngineManager;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase;
use Scout\Solr\Client;
use Scout\Solr\ClientInterface;
use Scout\Solr\Engines\SolrEngine;
use Scout\Solr\ScoutSolrServiceProvider;
use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Adapter\Http;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ScoutServiceProvider::class, ScoutSolrServiceProvider::class];
    }

    public function test_engine_is_registered_as_solr(): void
    {
        $this->assertInstanceOf(SolrEngine::class, $this->app->make(EngineManager::class)->engine('solr'));
        $this->assertSame($this->app->make(ClientInterface::class), $this->app->make(Client::class));
    }

    public function test_curl_adapter_with_default_timeout(): void
    {
        $adapter = $this->app->make(ClientInterface::class)->getAdapter();

        $this->assertInstanceOf(Curl::class, $adapter);
        $this->assertSame(5, $adapter->getTimeout());
    }

    public function test_adapter_and_timeouts_come_from_config(): void
    {
        $this->app['config']->set('scout-solr.http', [
            'adapter' => 'http',
            'timeout' => '30',
            'connection_timeout' => null,
        ]);

        $adapter = $this->app->make(ClientInterface::class)->getAdapter();

        $this->assertInstanceOf(Http::class, $adapter);
        $this->assertSame(30, $adapter->getTimeout());
    }

    public function test_connection_timeout_is_applied_to_curl(): void
    {
        $this->app['config']->set('scout-solr.http.connection_timeout', '2');

        $adapter = $this->app->make(ClientInterface::class)->getAdapter();

        $this->assertSame(2, $adapter->getConnectionTimeout());
    }

    public function test_an_adapter_bound_in_the_container_is_used(): void
    {
        $this->app->instance(AdapterInterface::class, $bound = new Http());

        $this->assertSame($bound, $this->app->make(ClientInterface::class)->getAdapter());
    }

    public function test_unknown_adapter_name_throws(): void
    {
        $this->app['config']->set('scout-solr.http.adapter', 'guzzle');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported Solr HTTP adapter "guzzle"');

        $this->app->make(ClientInterface::class);
    }

    public function test_default_endpoint_is_configured_from_config(): void
    {
        $this->app['config']->set('scout-solr.endpoints.default.host', 'solr.internal');

        $this->assertSame('solr.internal', $this->app->make(ClientInterface::class)->getEndpoint()->getHost());
    }
}
