<?php

declare(strict_types=1);

use App\Actions\DeletePasskey;
use App\Models\Passkey;
use App\Models\User;

it('removes the passkey, and no other', function (): void {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->for($user)->create();
    $otherPasskey = Passkey::factory()->for($user)->create();
    $passkeyOfAnotherUser = Passkey::factory()->create();

    app(DeletePasskey::class)->handle($passkey);

    expect(Passkey::query()->pluck('id')->all())->toEqualCanonicalizing([$otherPasskey->id, $passkeyOfAnotherUser->id]);
});
