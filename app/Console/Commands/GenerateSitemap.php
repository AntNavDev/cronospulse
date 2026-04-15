<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the public sitemap.xml';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Sitemap::create()
            ->add(
                Url::create('/')
                    ->setLastModificationDate(Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(1.0),
            )
            ->add(
                Url::create('/about')
                    ->setLastModificationDate(Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.8),
            )
            ->writeToFile(public_path('sitemap.xml'));

        $this->info('sitemap.xml generated successfully.');

        return self::SUCCESS;
    }
}
