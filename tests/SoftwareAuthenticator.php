<?php

declare(strict_types=1);

namespace Tests;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Illuminate\Support\Facades\Config;
use OpenSSLAsymmetricKey;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Plays the browser and the authenticator of a WebAuthn ceremony: it holds one P-256 credential,
 * and answers with the JSON that @simplewebauthn/browser posts.
 */
final class SoftwareAuthenticator
{
    private const int USER_PRESENT = 0x01;

    private const int USER_VERIFIED = 0x04;

    private const int ATTESTED_CREDENTIAL_DATA = 0x40;

    private ?OpenSSLAsymmetricKey $privateKey = null;

    private string $credentialId = '';

    private string $userHandle = '';

    private int $counter = 0;

    public function __construct(private readonly string $aaguid = '00000000-0000-0000-0000-000000000000') {}

    /**
     * @return array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, attestationObject: string, transports: list<string>}, clientExtensionResults: array{}, authenticatorAttachment: string}
     */
    public function register(PublicKeyCredentialCreationOptions $options, ?string $origin = null, string $type = 'webauthn.create', bool $isCrossOrigin = false): array
    {
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);

        if ($privateKey === false) {
            throw new RuntimeException('OpenSSL could not generate a P-256 key.');
        }

        $this->privateKey = $privateKey;
        $this->credentialId = random_bytes(32);
        $this->userHandle = $options->user->id;

        $attestedCredentialData = Uuid::fromString($this->aaguid)->toBinary()
            .pack('n', strlen($this->credentialId)).$this->credentialId
            .$this->coseKey($privateKey);

        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create(
                $this->authenticatorData((string) $options->rp->id, self::ATTESTED_CREDENTIAL_DATA).$attestedCredentialData,
            ));

        return [
            'id' => $this->base64Url($this->credentialId),
            'rawId' => $this->base64Url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->base64Url($this->clientData($type, $options->challenge, $origin, $isCrossOrigin)),
                'attestationObject' => $this->base64Url((string) $attestationObject),
                'transports' => ['internal'],
            ],
            'clientExtensionResults' => [],
            'authenticatorAttachment' => 'platform',
        ];
    }

    /**
     * @return array{id: string, rawId: string, type: string, response: array{clientDataJSON: string, authenticatorData: string, signature: string, userHandle: string}, clientExtensionResults: array{}, authenticatorAttachment: string}
     */
    public function authenticate(PublicKeyCredentialRequestOptions $options, ?string $origin = null, string $type = 'webauthn.get', bool $isCrossOrigin = false): array
    {
        if (! $this->privateKey instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('The authenticator holds no credential: register one first.');
        }

        // A security key counts its signatures; a synced passkey keeps 0.
        $this->counter++;

        $authenticatorData = $this->authenticatorData((string) $options->rpId);
        $clientData = $this->clientData($type, $options->challenge, $origin, $isCrossOrigin);

        openssl_sign($authenticatorData.hash('sha256', $clientData, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return [
            'id' => $this->base64Url($this->credentialId),
            'rawId' => $this->base64Url($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->base64Url($clientData),
                'authenticatorData' => $this->base64Url($authenticatorData),
                'signature' => $this->base64Url($signature),
                'userHandle' => $this->base64Url($this->userHandle),
            ],
            'clientExtensionResults' => [],
            'authenticatorAttachment' => 'platform',
        ];
    }

    private function authenticatorData(string $relyingPartyId, int $flags = 0): string
    {
        return hash('sha256', $relyingPartyId, true)
            .chr(self::USER_PRESENT | self::USER_VERIFIED | $flags)
            .pack('N', $this->counter);
    }

    private function clientData(string $type, string $challenge, ?string $origin, bool $isCrossOrigin): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $this->base64Url($challenge),
            'origin' => $origin ?? Config::string('app.url'),
            'crossOrigin' => $isCrossOrigin,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function coseKey(OpenSSLAsymmetricKey $privateKey): string
    {
        $details = openssl_pkey_get_details($privateKey);
        $x = data_get($details, 'ec.x');
        $y = data_get($details, 'ec.y');

        if (! is_string($x) || ! is_string($y)) {
            throw new RuntimeException('OpenSSL could not read the P-256 key.');
        }

        // A COSE key, as authenticators send it: key type EC2, algorithm ES256, curve P-256, then the coordinates.
        return (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(str_pad($x, 32, "\0", STR_PAD_LEFT)))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(str_pad($y, 32, "\0", STR_PAD_LEFT)));
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
