<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('About — CronosPulse')]
class About extends Component
{
    /**
     * Render the about page component.
     */
    public function render(): View
    {
        return view('livewire.pages.about');
    }
}
