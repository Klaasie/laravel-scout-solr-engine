<?php

namespace App\View\Components\Button;

use Illuminate\View\Component;

class Block extends Component
{
    public string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function render()
    {
        return view('components.button.block');
    }
}
