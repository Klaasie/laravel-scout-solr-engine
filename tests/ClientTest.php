<?php

namespace Scout\Solr\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scout\Solr\Client;
use Scout\Solr\Exceptions\InvalidRouterNameSupplied;
use Scout\Solr\Tests\Fixtures\SearchableModel;
use Solarium\Core\Client\Adapter\Curl;

class ClientTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('scout-solr.endpoints', [
            'default' => ['host' => 'solr-default', 'port' => 8983, 'path' => '/'],
            'books' => ['host' => 'solr-books', 'port' => 8984, 'path' => '/'],
            'archive' => ['host' => 'solr-archive', 'port' => 8983, 'path' => '/', 'core' => 'archive_v2'],
        ]);
    }

    public function test_endpoint_for_uses_the_default_instance_and_the_model_core(): void
    {
        // SearchableModel::searchableAs() returns 'table'.
        $endpoint = $this->create_client()->endpointFor(new SearchableModel());

        $this->assertSame('solr-default', $endpoint->getHost());
        $this->assertSame('table', $endpoint->getCore());
    }

    public function test_endpoint_for_uses_a_config_keyed_by_the_core_name(): void
    {
        $endpoint = $this->create_client()->endpointFor('books');

        $this->assertSame('solr-books', $endpoint->getHost());
        $this->assertSame(8984, $endpoint->getPort());
        $this->assertSame('books', $endpoint->getCore());
    }

    public function test_endpoint_for_respects_a_configured_core(): void
    {
        $this->assertSame('archive_v2', $this->create_client()->endpointFor('archive')->getCore());
    }

    public function test_endpoints_are_reused_and_do_not_change_the_default_endpoint(): void
    {
        $client = $this->create_client();

        $first = $client->endpointFor('authors');
        $client->endpointFor('books');

        $this->assertSame($first, $client->endpointFor('authors'));
        $this->assertSame('authors', $first->getCore());
        $this->assertNull($client->getEndpoint()->getCore());
    }

    public function test_create_core_throws_for_unsupported_router_name(): void
    {
        Config::set('scout-solr.cloud', true);
        Config::set('scout-solr.endpoints.default.router_name', 'bogus');

        $this->expectException(InvalidRouterNameSupplied::class);
        $this->expectExceptionMessage('Router name bogus is not supported');

        $this->create_client()->createCore('books_cloud');
    }

    private function create_client(): Client
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };

        return new Client(new Curl(), $dispatcher, $this->app->make(Repository::class));
    }
}
