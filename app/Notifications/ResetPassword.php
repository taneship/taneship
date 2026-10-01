<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use SensitiveParameter;

final class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(#[SensitiveParameter] public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        // The broker that checks the token reads the same lifetime.
        $minutes = Config::integer('auth.passwords.'.Config::string('auth.defaults.passwords').'.expire');

        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return new MailMessage()
            ->subject(__('identity.password.mail.subject'))
            ->line(__('identity.password.mail.instruction'))
            ->action(__('identity.password.mail.action'), $url)
            ->line(__('identity.password.mail.expiration', ['count' => $minutes]))
            ->line(__('identity.password.mail.ignore'));
    }
}
