<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PasswordResetData
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
    ) {}
}
