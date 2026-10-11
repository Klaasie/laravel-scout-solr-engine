<?php

namespace Scout\Solr\Tests\Fixtures;

use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Response;

/**
 * A Solarium adapter that records requests instead of sending them, and answers
 * every request with an empty successful Solr response.
 */
class RecordingAdapter implements AdapterInterface
{
    /** @var list<array{request: Request, endpoint: Endpoint}> */
    public array $requests = [];

    public function execute(Request $request, Endpoint $endpoint): Response
    {
        $this->requests[] = ['request' => $request, 'endpoint' => $endpoint];

        return new Response('{"responseHeader":{"status":0,"QTime":1}}', ['HTTP/1.1 200 OK']);
    }

    /**
     * @return array{request: Request, endpoint: Endpoint}
     */
    public function lastRequest(): array
    {
        return $this->requests[array_key_last($this->requests)];
    }
}
