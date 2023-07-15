<?php

declare(strict_types=1);

namespace Scout\Solr;

use Illuminate\Database\Eloquent\Model;
use Solarium\Core\Client\ClientInterface as ClientInterfaceBase;

interface ClientInterface extends ClientInterfaceBase
{
    public const ROUTER_NAME_COMPOSITE_ID = 'compositeId';
    public const ROUTER_NAME_IMPLICIT = 'implicit';

    public function setCore(Model $model): self;

    public function createCore(string $name);

    public function deleteCore(string $name);
}
