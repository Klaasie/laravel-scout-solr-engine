<?php

namespace App\View\Components\Forms\Input;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Password extends Component
{
    public string $label;
    public string $name;
    public ?string $placeholder;

    public function __construct(string $label, string $name, ?string $placeholder)
    {
        $this->label = $label;
        $this->name = $name;
        $this->placeholder = $placeholder;
    }

    public function render(): View
    {
        return view('components.forms.input.password');
    }
}
