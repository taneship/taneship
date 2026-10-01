<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PasskeyFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\CredentialRecord;

/**
 * Eloquent sets created_at on insert, although timestamps() leaves the column nullable.
 *
 * @property-read CarbonImmutable $created_at
 */
#[Hidden(['credential'])]
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
     * @return Attribute<CredentialRecord, CredentialRecord>
     */
    protected function credential(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $credential): CredentialRecord => app(SerializerInterface::class)->deserialize($credential, CredentialRecord::class, 'json'),
            set: fn (CredentialRecord $record): array => [
                'credential' => app(SerializerInterface::class)->serialize($record, 'json'),
                // Lookups and the unique index need the id in a column of its own: base64url, as the record writes it.
                'credential_id' => rtrim(strtr(base64_encode($record->publicKeyCredentialId), '+/', '-_'), '='),
            ],
        );
    }

    /**
     * @return Attribute<?string, never>
     */
    protected function holder(): Attribute
    {
        // Looked up at each read, so an updated list renames the passkeys already registered.
        return Attribute::get(function (): ?string {
            $holder = Config::array('passkeys.authenticators')[$this->credential->aaguid->toRfc4122()] ?? null;

            return is_string($holder) ? $holder : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }
}
