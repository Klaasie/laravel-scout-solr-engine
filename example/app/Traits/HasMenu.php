<?php

namespace App\Traits;

use Illuminate\Contracts\View\View;

trait HasMenu
{
    public function setMenu(string $active): void
    {
        view()->composer('components.menu', static function (View $view) use ($active) {
            $view->with('menu', $active);
        });
    }
}
