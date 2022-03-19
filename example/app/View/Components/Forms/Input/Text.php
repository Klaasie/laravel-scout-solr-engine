<?php

namespace App\View\Components\Forms\Input;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Text extends Component
{
    public string $label;
    public string $name;
    public ?string $value;

    public function __construct(string $label, string $name, ?string $value)
    {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
    }

    public function render(): View
    {
        return view('components.forms.input.text');
    }
}
