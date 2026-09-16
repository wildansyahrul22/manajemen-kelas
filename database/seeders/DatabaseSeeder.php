<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Minimal production-ready seed: reference semesters 1-14, the first campus and one super admin.
 * Demo data is opt-in: `php artisan db:seed --class=DemoSeeder`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SemesterSeeder::class, KampusSeeder::class]);

        User::query()->firstOrCreate(
            ['npm' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'no_hp' => '6281200000001',
                'password' => 'password',
                'role' => Role::SuperAdmin,
                'kelas_id' => null,
            ],
        );
    }
}
