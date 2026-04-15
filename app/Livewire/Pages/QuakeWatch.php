<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('QuakeWatch — Earthquake Radius Search | CronosPulse')]
class QuakeWatch extends Component
{
    /**
     * Render the QuakeWatch page.
     */
    public function render(): View
    {
        return view('livewire.pages.quake-watch');
    }
}
