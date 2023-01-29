<?php

namespace App\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Error extends Component
{
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function render(): View
    {
        return view('components.forms.error');
    }
}
