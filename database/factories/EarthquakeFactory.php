<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Earthquake>
 */
class EarthquakeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usgs_id'        => 'us' . $this->faker->unique()->regexify('[a-z0-9]{8}'),
            'magnitude'      => $this->faker->randomFloat(1, 4.0, 7.5),
            'magnitude_type' => $this->faker->randomElement(['mw', 'ml', 'mb', 'md']),
            'depth_km'       => $this->faker->randomFloat(3, 1.0, 300.0),
            'latitude'       => $this->faker->latitude(),
            'longitude'      => $this->faker->longitude(),
            'place'          => $this->faker->numerify('## km ') . $this->faker->randomElement(['N', 'NE', 'NW', 'S', 'SE', 'SW', 'E', 'W']) . ' of ' . $this->faker->city(),
            'status'         => $this->faker->randomElement(['automatic', 'reviewed']),
            'alert'          => null,
            'felt'           => null,
            'cdi'            => null,
            'mmi'            => null,
            'significance'   => $this->faker->numberBetween(100, 800),
            'url'            => 'https://earthquake.usgs.gov/earthquakes/eventpage/' . $this->faker->regexify('[a-z0-9]{10}'),
            'occurred_at'    => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ];
    }

    /**
     * Mark the event with a PAGER alert level.
     */
    public function withAlert(string $level = 'green'): static
    {
        return $this->state(['alert' => $level]);
    }
}
