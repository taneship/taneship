<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    // Hashing is slow on purpose: every user of a test run shares one hash.
    private static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // A created model holds only the attributes given here, and strict mode refuses to read a missing one.
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function withTwoFactorAuthentication(): self
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => new Google2FA()->generateSecretKey(32),
            'two_factor_recovery_codes' => User::generateRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
