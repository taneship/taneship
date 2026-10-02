<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialOptions;

/**
 * A ceremony spans two requests: a page asks for its options, then posts the credential that answers them.
 * The session keeps the options in between, challenge included, under one key per ceremony: the options
 * a page loaded never serve the ceremony of another.
 */
final readonly class PasskeyCeremony
{
    public const string REGISTRATION = 'passkeys.registration_options';

    public const string SIGN_IN = 'passkeys.authentication_options';

    public const string CONFIRMATION = 'passkeys.confirmation_options';

    public function __construct(
        private Session $session,
        private SerializerInterface $serializer,
    ) {}

    /**
     * Keeps the options for the credential to come, and returns them as the browser reads them.
     */
    public function start(string $ceremony, PublicKeyCredentialOptions $options): mixed
    {
        // Without null values: the browser refuses a null where WebAuthn expects a value, such as authenticatorAttachment.
        // The session keeps that very JSON, which reads back as the same options.
        $json = $this->serializer->serialize($options, 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);

        $this->session->put($ceremony, $json);

        return json_decode($json, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @template TOptions of PublicKeyCredentialOptions
     *
     * @param  class-string<TOptions>  $class
     * @return TOptions
     */
    public function finish(string $ceremony, string $class): PublicKeyCredentialOptions
    {
        // Pulled, so that a ceremony serves once: a replayed credential finds no options.
        $json = $this->session->pull($ceremony);

        if (! is_string($json)) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        return $this->serializer->deserialize($json, $class, 'json');
    }
}
