<?php

namespace App\View\Components\Forms\Input;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Email extends Component
{
    public string $label;
    public string $name;
    public ?string $value;
    public ?string $placeholder;

    public function __construct(string $label, string $name, ?string $value, ?string $placeholder)
    {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->placeholder = $placeholder;
    }

    public function render(): View
    {
        return view('components.forms.input.email');
    }
}
