<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ProfileData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
