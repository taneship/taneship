<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Passkey;

final readonly class DeletePasskey
{
    public function handle(Passkey $passkey): void
    {
        $passkey->delete();
    }
}
