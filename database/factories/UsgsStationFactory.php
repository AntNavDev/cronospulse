<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\UsgsStation>
 */
class UsgsStationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_no'      => $this->faker->unique()->numerify('0########'),
            'name'         => $this->faker->city() . ' River at ' . $this->faker->city(),
            'state'        => strtoupper($this->faker->stateAbbr()),
            'county'       => null,
            'huc'          => null,
            'site_type'    => 'ST',
            'latitude'     => (float) $this->faker->latitude(24.0, 49.0),
            'longitude'    => (float) $this->faker->longitude(-125.0, -66.0),
            'elevation_ft' => null,
            'is_active'    => true,
        ];
    }
}
