<?php

namespace App\Providers;

use App\Listeners\IncludeQueryTime;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $subscribe = [
        IncludeQueryTime::class,
    ];
}
