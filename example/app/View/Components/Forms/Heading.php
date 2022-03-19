<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class Heading extends Component
{
    public string $icon;
    public string $title;
    public ?string $subtitle;

    public function __construct(string $icon, string $title, ?string $subtitle = null)
    {
        $this->icon = $icon;
        $this->title = $title;
        $this->subtitle = $subtitle;
    }

    public function render()
    {
        return view('components.forms.heading');
    }
}
