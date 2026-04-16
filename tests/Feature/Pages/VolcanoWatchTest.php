<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Data\VolcanoData;
use App\Livewire\Pages\VolcanoWatch;
use App\Services\VolcanoService;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class VolcanoWatchTest extends TestCase
{
    /**
     * The VolcanoWatch page loads successfully.
     */
    public function test_volcano_watch_page_returns_ok(): void
    {
        $this->get(route('volcano-watch'))
            ->assertOk();
    }

    /**
     * The component defaults to 10 results per page and paginates the full list correctly.
     */
    public function test_paginator_defaults_to_ten_per_page(): void
    {
        $this->mockVolcanoService($this->makeVolcanoes(25));

        Livewire::test(VolcanoWatch::class)
            ->assertSet('perPage', 10)
            ->assertViewHas('filteredCount', 25)
            ->assertViewHas('paginator', fn ($p) => $p->perPage() === 10)
            ->assertViewHas('paginator', fn ($p) => $p->total() === 25)
            ->assertViewHas('paginator', fn ($p) => count($p->items()) === 10);
    }

    /**
     * Advancing to the last page shows only the remaining items.
     */
    public function test_setpage_advances_to_next_page(): void
    {
        $this->mockVolcanoService($this->makeVolcanoes(25));

        Livewire::test(VolcanoWatch::class)
            ->call('setPage', 3)
            ->assertViewHas('paginator', fn ($p) => count($p->items()) === 5); // last 5 of 25
    }

    /**
     * The name search filters the result list (case-insensitive).
     */
    public function test_search_query_filters_by_name(): void
    {
        $volcanoes = new Collection([
            $this->makeVolcano('NORMAL', 'Mount Rainier', '1'),
            $this->makeVolcano('NORMAL', 'Mount Shasta', '2'),
            $this->makeVolcano('NORMAL', 'Kilauea', '3'),
        ]);

        $this->mockVolcanoService($volcanoes);

        Livewire::test(VolcanoWatch::class)
            ->set('searchQuery', 'mount')
            ->assertViewHas('filteredCount', 2); // Rainier + Shasta match 'mount'
    }

    /**
     * Search for a term that matches no volcanoes returns zero results.
     */
    public function test_search_query_with_no_match_returns_zero(): void
    {
        $this->mockVolcanoService($this->makeVolcanoes(5, 'Shasta'));

        Livewire::test(VolcanoWatch::class)
            ->set('searchQuery', 'kilauea')
            ->assertViewHas('filteredCount', 0);
    }

    /**
     * The elevated counts reflect counts from the full unfiltered list.
     */
    public function test_elevated_counts_reflect_full_list(): void
    {
        $volcanoes = new Collection([
            $this->makeVolcano('WARNING', 'Volcano A', '1'),
            $this->makeVolcano('WARNING', 'Volcano B', '2'),
            $this->makeVolcano('WATCH', 'Volcano C', '3'),
            $this->makeVolcano('ADVISORY', 'Volcano D', '4'),
            $this->makeVolcano('NORMAL', 'Volcano E', '5'),
        ]);

        $this->mockVolcanoService($volcanoes);

        Livewire::test(VolcanoWatch::class)
            ->assertViewHas('elevatedCounts', fn ($c) => $c['WARNING'] === 2)
            ->assertViewHas('elevatedCounts', fn ($c) => $c['WATCH'] === 1)
            ->assertViewHas('elevatedCounts', fn ($c) => $c['ADVISORY'] === 1);
    }

    /**
     * Chart labels only include levels that have at least one volcano.
     */
    public function test_chart_data_excludes_levels_with_zero_count(): void
    {
        $volcanoes = new Collection([
            $this->makeVolcano('NORMAL', 'Volcano A', '1'),
            $this->makeVolcano('NORMAL', 'Volcano B', '2'),
            $this->makeVolcano('ADVISORY', 'Volcano C', '3'),
        ]);

        $this->mockVolcanoService($volcanoes);

        Livewire::test(VolcanoWatch::class)
            ->assertViewHas('chartLabels', fn ($labels) => ! in_array('Warning', $labels, true))
            ->assertViewHas('chartLabels', fn ($labels) => ! in_array('Watch', $labels, true))
            ->assertViewHas('chartLabels', fn ($labels) => in_array('Advisory', $labels, true))
            ->assertViewHas('chartLabels', fn ($labels) => in_array('Normal', $labels, true));
    }

    /**
     * Bind a fake VolcanoService that returns a preset collection.
     */
    private function mockVolcanoService(Collection $volcanoes): void
    {
        $mock = $this->createMock(VolcanoService::class);
        $mock->method('all')->willReturn($volcanoes);
        $this->app->instance(VolcanoService::class, $mock);
    }

    /**
     * Create N VolcanoData instances with an optional name prefix.
     */
    private function makeVolcanoes(int $count, string $namePrefix = 'Volcano'): Collection
    {
        return collect(range(1, $count))->map(
            fn ($i) => $this->makeVolcano('NORMAL', "{$namePrefix} {$i}", (string) $i),
        );
    }

    /**
     * Create a single VolcanoData instance with the given alert level.
     */
    private function makeVolcano(string $alertLevel, string $name = 'Test Volcano', string $vnum = '1'): VolcanoData
    {
        return new VolcanoData(
            vnum: $vnum,
            name: $name,
            region: 'Alaska',
            latitude: 60.0,
            longitude: -150.0,
            alertLevel: $alertLevel,
            colorCode: 'GREEN',
            synopsis: null,
            url: null,
        );
    }
}
