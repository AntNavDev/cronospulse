<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Real-Time Geophysical Data | CronosPulse')]
class Home extends Component
{
    /**
     * Render the home page component.
     */
    public function render(): View
    {
        return view('livewire.pages.home');
    }
}
