<?php

namespace Database\Seeders;

use App\Models\Kampus;
use Illuminate\Database\Seeder;

class KampusSeeder extends Seeder
{
    public function run(): void
    {
        Kampus::query()->firstOrCreate(['nama' => Kampus::AWAL]);
    }
}
