<?php

declare(strict_types=1);

namespace Scout\Solr;

use Illuminate\Database\Eloquent\Model;
use Solarium\Core\Client\ClientInterface as ClientInterfaceBase;

interface ClientInterface extends ClientInterfaceBase
{
    public function setCore(Model $model): self;
}
