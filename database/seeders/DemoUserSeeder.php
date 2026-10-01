<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // composer setup seeds on every run.
        if (User::query()->where('email', 'demo@example.com')->exists()) {
            return;
        }

        // Without the factory: it needs Faker, which an environment installed with --no-dev lacks.
        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'password',
        ])->markEmailAsVerified();
    }
}
