<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PasskeyFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;

/**
 * Eloquent sets created_at on insert, although timestamps() leaves the column nullable.
 *
 * @property-read CarbonImmutable $created_at
 */
final class Passkey extends Model
{
    /** @use HasFactory<PasskeyFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Attribute<?string, never>
     */
    protected function holder(): Attribute
    {
        // Looked up at each read, so an updated list renames the passkeys already registered.
        return Attribute::get(function (): ?string {
            $aaguid = $this->credential['aaguid'] ?? null;

            if (! is_string($aaguid)) {
                return null;
            }

            $holder = Config::array('passkeys.authenticators')[$aaguid] ?? null;

            return is_string($holder) ? $holder : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credential' => 'array',
            'last_used_at' => 'datetime',
        ];
    }
}
