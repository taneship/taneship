<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @extends Factory<Passkey>
 */
final class PasskeyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $addedAt = fake()->dateTimeBetween('-1 year');

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['MacBook Pro', 'iPhone', 'Pixel', 'Work laptop']),
            // The length of a 32-byte credential id in base64url.
            'credential_id' => Str::random(43),
            'credential' => [
                'aaguid' => fake()->randomElement(array_keys(Config::array('passkeys.authenticators'))),
            ],
            'last_used_at' => fake()->dateTimeBetween($addedAt),
            'created_at' => $addedAt,
        ];
    }
}
