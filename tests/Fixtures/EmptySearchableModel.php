<?php

namespace Scout\Solr\Tests\Fixtures;

class EmptySearchableModel extends SearchableModel
{
    public function toSearchableArray(): array
    {
        return [];
    }
}
