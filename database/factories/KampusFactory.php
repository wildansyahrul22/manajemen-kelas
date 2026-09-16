<?php

namespace Database\Factories;

use App\Models\Kampus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kampus>
 */
class KampusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Universitas '.fake()->unique()->city(),
        ];
    }
}
