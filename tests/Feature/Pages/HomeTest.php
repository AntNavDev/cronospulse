<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use Tests\TestCase;

class HomeTest extends TestCase
{
    /**
     * The home page loads successfully.
     */
    public function test_home_page_returns_ok(): void
    {
        $this->get(route('home'))
            ->assertOk();
    }
}
