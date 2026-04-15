<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Serve the robots.txt file dynamically so the Sitemap URL
     * always reflects the current APP_URL environment value.
     */
    public function __invoke(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
            '',
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
