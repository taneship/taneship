<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SignInData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember,
    ) {}
}
