<?php

declare(strict_types=1);

namespace Scout\Solr\Exceptions;

use InvalidArgumentException;

class InvalidRouterNameSupplied extends InvalidArgumentException
{
    public static function forRouterName(string $name): self
    {
        return new self(
            sprintf('Router name %s is not supported', $name)
        );
    }
}