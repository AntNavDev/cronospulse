<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use Tests\TestCase;

class QuakeWatchTest extends TestCase
{
    /**
     * The QuakeWatch page loads successfully.
     */
    public function test_quake_watch_page_returns_ok(): void
    {
        $this->get(route('quake-watch'))
            ->assertOk();
    }
}