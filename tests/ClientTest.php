<?php

namespace Scout\Solr\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scout\Solr\Client;
use Scout\Solr\Exceptions\InvalidRouterNameSupplied;
use Solarium\Core\Client\Adapter\Curl;

class ClientTest extends TestCase
{
    public function test_create_core_throws_for_unsupported_router_name(): void
    {
        Config::set('scout-solr.cloud', true);
        Config::set('scout-solr.endpoints.default', [
            'host' => 'localhost',
            'port' => 8983,
            'path' => '/',
            'router_name' => 'bogus',
        ]);

        $this->expectException(InvalidRouterNameSupplied::class);
        $this->expectExceptionMessage('Router name bogus is not supported');

        $this->create_client()->createCore('books');
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
