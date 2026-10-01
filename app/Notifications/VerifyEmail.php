<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $minutes = 60;

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $notifiable->id,
            'hash' => hash('sha256', $notifiable->getEmailForVerification()),
        ]);

        return new MailMessage()
            ->subject(__('identity.verification.mail.subject'))
            ->line(__('identity.verification.mail.instruction'))
            ->action(__('identity.verification.mail.action'), $url)
            ->line(__('identity.verification.mail.expiration', ['count' => $minutes]))
            ->line(__('identity.verification.mail.ignore'));
    }
}
