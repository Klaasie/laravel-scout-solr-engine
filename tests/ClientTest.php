<?php

namespace Scout\Solr\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scout\Solr\Client;
use Scout\Solr\Exceptions\InvalidRouterNameSupplied;
use Scout\Solr\Tests\Fixtures\RecordingAdapter;
use Scout\Solr\Tests\Fixtures\SearchableModel;
use Solarium\Core\Client\Adapter\AdapterInterface;
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

    public function test_create_core_uses_the_config_set_of_its_endpoint(): void
    {
        Config::set('scout-solr.endpoints.books.config_set', 'books_config');

        [$request, $endpoint] = $this->admin_request(fn (Client $client) => $client->createCore('books'));

        $this->assertSame('admin/cores', $request->getHandler());
        $this->assertSame('CREATE', $request->getParam('action'));
        $this->assertSame('books', $request->getParam('name'));
        $this->assertSame('books_config', $request->getParam('configSet'));
        $this->assertSame('solr-books', $endpoint->getHost());
        $this->assertNull($endpoint->getCore());
    }

    public function test_create_core_falls_back_to_the_default_endpoint_and_config_set(): void
    {
        [$request, $endpoint] = $this->admin_request(fn (Client $client) => $client->createCore('authors'));

        $this->assertSame('authors', $request->getParam('name'));
        $this->assertSame('_default', $request->getParam('configSet'));
        $this->assertSame('solr-default', $endpoint->getHost());
    }

    public function test_delete_core_unloads_with_the_configured_flags(): void
    {
        Config::set('scout-solr.unload', [
            'delete_index' => true,
            'delete_data_dir' => false,
            'delete_instance_dir' => true,
        ]);

        [$request, $endpoint] = $this->admin_request(fn (Client $client) => $client->deleteCore('books'));

        $this->assertSame('admin/cores', $request->getHandler());
        $this->assertSame('UNLOAD', $request->getParam('action'));
        $this->assertSame('books', $request->getParam('core'));
        $this->assertSame('true', $request->getParam('deleteIndex'));
        $this->assertSame('false', $request->getParam('deleteDataDir'));
        $this->assertSame('true', $request->getParam('deleteInstanceDir'));
        $this->assertSame('solr-books', $endpoint->getHost());
    }

    public function test_cloud_create_with_composite_id_router_sets_the_number_of_shards(): void
    {
        Config::set('scout-solr.cloud', true);
        Config::set('scout-solr.endpoints.default.router_name', 'compositeId');
        Config::set('scout-solr.endpoints.default.num_shards', '3');

        [$request, $endpoint] = $this->admin_request(fn (Client $client) => $client->createCore('authors'));

        $this->assertSame('admin/collections', $request->getHandler());
        $this->assertSame('CREATE', $request->getParam('action'));
        $this->assertSame('authors', $request->getParam('name'));
        $this->assertSame('compositeId', $request->getParam('router.name'));
        $this->assertEquals(3, $request->getParam('numShards'));
        $this->assertNull($request->getParam('shards'));
        $this->assertSame('solr-default', $endpoint->getHost());
    }

    public function test_cloud_create_with_implicit_router_sets_the_shard_names(): void
    {
        Config::set('scout-solr.cloud', true);
        Config::set('scout-solr.endpoints.books.router_name', 'implicit');
        Config::set('scout-solr.endpoints.books.shards', 'shard-a,shard-b');

        [$request, $endpoint] = $this->admin_request(fn (Client $client) => $client->createCore('books'));

        $this->assertSame('implicit', $request->getParam('router.name'));
        $this->assertSame('shard-a,shard-b', $request->getParam('shards'));
        $this->assertNull($request->getParam('numShards'));
        $this->assertSame('solr-books', $endpoint->getHost());
    }

    public function test_cloud_delete_deletes_the_collection(): void
    {
        Config::set('scout-solr.cloud', true);

        [$request, $endpoint] = $this->admin_request(fn (Client $client) => $client->deleteCore('archive'));

        $this->assertSame('admin/collections', $request->getHandler());
        $this->assertSame('DELETE', $request->getParam('action'));
        $this->assertSame('archive', $request->getParam('name'));
        $this->assertSame('solr-archive', $endpoint->getHost());
        $this->assertNull($endpoint->getCore());
    }

    /**
     * Runs an admin call against a recording adapter and returns the request and endpoint it sent.
     *
     * @return array{0: \Solarium\Core\Client\Request, 1: \Solarium\Core\Client\Endpoint}
     */
    private function admin_request(callable $call): array
    {
        $adapter = new RecordingAdapter();
        $call($this->create_client($adapter));

        $this->assertCount(1, $adapter->requests);
        ['request' => $request, 'endpoint' => $endpoint] = $adapter->lastRequest();

        return [$request, $endpoint];
    }

    private function create_client(?AdapterInterface $adapter = null): Client
    {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };

        return new Client($adapter ?? new Curl(), $dispatcher, $this->app->make(Repository::class));
    }
}
