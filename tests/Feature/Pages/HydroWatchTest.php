<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use Tests\TestCase;

class HydroWatchTest extends TestCase
{
    /**
     * The HydroWatch page loads successfully.
     */
    public function test_hydro_watch_page_returns_ok(): void
    {
        $this->get(route('hydro-watch'))
            ->assertOk();
    }
}
