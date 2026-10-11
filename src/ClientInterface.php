<?php

declare(strict_types=1);

namespace Scout\Solr;

use Illuminate\Database\Eloquent\Model;
use Solarium\Core\Client\ClientInterface as ClientInterfaceBase;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Query\Result\ResultInterface;

interface ClientInterface extends ClientInterfaceBase
{
    public const ROUTER_NAME_COMPOSITE_ID = 'compositeId';
    public const ROUTER_NAME_IMPLICIT = 'implicit';

    /**
     * The endpoint for a model's core, resolved from its searchableAs() name.
     */
    public function endpointFor(Model|string $model): Endpoint;

    public function createCore(string $name): ResultInterface;

    public function deleteCore(string $name): ResultInterface;
}
