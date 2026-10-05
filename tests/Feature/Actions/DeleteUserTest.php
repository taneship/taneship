<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

it('deletes the user, and no other', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    app(DeleteUser::class)->handle($user);

    expect(User::query()->pluck('id')->all())->toBe([$otherUser->id]);
});

it('removes the passkeys of the user, and no other', function (): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->count(2)->create();
    $passkeyOfAnotherUser = Passkey::factory()->create();

    app(DeleteUser::class)->handle($user);

    expect(Passkey::query()->pluck('id')->all())->toBe([$passkeyOfAnotherUser->id]);
});

it('removes the password reset tokens of the address, and no other', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Password::createToken($user);
    Password::createToken($otherUser);

    app(DeleteUser::class)->handle($user);

    expect(DB::table('password_reset_tokens')->pluck('email')->all())->toBe([$otherUser->email]);
});
