<?php

declare(strict_types=1);

namespace App\Data;

use Webauthn\PublicKeyCredential;

final readonly class PasskeySignInData
{
    public function __construct(
        public PublicKeyCredential $credential,
        public bool $remember,
    ) {}
}
