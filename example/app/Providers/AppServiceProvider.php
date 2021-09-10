<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Scout\Solr\ScoutSolrServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ScoutSolrServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
