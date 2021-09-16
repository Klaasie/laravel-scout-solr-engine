<?php

namespace App\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Scout\Solr\Events\AfterSelect;
use Scout\Solr\Events\BeforeSelect;

class IncludeQueryTime
{
    private Factory $view;

    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    public function handleQueryTimeSetting(BeforeSelect $event):void
    {
        $event->query->setOmitHeader(false);
    }

    public function handleQueryTimeVariable(AfterSelect $event): void
    {
        $this->view->share('queryTime', $event->result->getQueryTime());
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(BeforeSelect::class, [self::class, 'handleQueryTimeSetting']);
        $events->listen(AfterSelect::class, [self::class, 'handleQueryTimeVariable']);
    }
}
