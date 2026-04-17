<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('HydroWatch — Stream & Flood Monitoring | CronosPulse')]
class HydroWatch extends Component
{
    /**
     * Render the HydroWatch page.
     *
     * This component is a thin container. All data fetching and state lives
     * in the embedded StreamGauge and FloodWatch sub-components.
     */
    public function render(): View
    {
        return view('livewire.pages.hydro-watch');
    }
}
