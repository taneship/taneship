<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PasskeyData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $holder,
        public string $added,
        public ?string $lastUsed,
    ) {}
}
