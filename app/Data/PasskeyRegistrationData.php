<?php

declare(strict_types=1);

namespace App\Data;

use Webauthn\PublicKeyCredential;

final readonly class PasskeyRegistrationData
{
    public function __construct(
        public string $name,
        public PublicKeyCredential $credential,
    ) {}
}
