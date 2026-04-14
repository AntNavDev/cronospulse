<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use Tests\TestCase;

class AboutTest extends TestCase
{
    /**
     * The about page loads successfully.
     */
    public function test_about_page_returns_ok(): void
    {
        $this->get(route('about'))
            ->assertOk();
    }
}
