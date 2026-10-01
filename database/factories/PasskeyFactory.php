<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Passkey;
use App\Models\User;
use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\UnsignedIntegerObject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\TrustPath\EmptyTrustPath;

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
            'credential' => $this->credentialRecord(collect(Config::array('passkeys.authenticators'))->keys()->random()),
            'last_used_at' => fake()->dateTimeBetween($addedAt),
            'created_at' => $addedAt,
        ];
    }

    public function withAaguid(string $aaguid): self
    {
        return $this->state(fn (): array => ['credential' => $this->credentialRecord($aaguid)]);
    }

    private function credentialRecord(string $aaguid): CredentialRecord
    {
        return CredentialRecord::create(
            publicKeyCredentialId: random_bytes(32),
            type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            transports: [PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_INTERNAL],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString($aaguid),
            credentialPublicKey: $this->publicKey(),
            userHandle: random_bytes(32),
            counter: 0,
        );
    }

    private function publicKey(): string
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $details = $key === false ? false : openssl_pkey_get_details($key);
        $x = data_get($details, 'ec.x');
        $y = data_get($details, 'ec.y');

        if (! is_string($x) || ! is_string($y)) {
            throw new RuntimeException('OpenSSL could not generate a P-256 key.');
        }

        // A COSE key, as authenticators send it: key type EC2, algorithm ES256, curve P-256, then the coordinates.
        return (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(str_pad($x, 32, "\0", STR_PAD_LEFT)))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(str_pad($y, 32, "\0", STR_PAD_LEFT)));
    }
}
