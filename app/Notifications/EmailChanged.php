<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

// Sent to the former address of a user, which is no longer theirs: the notifiable is that address, not the user.
final class EmailChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $newEmail) {}

    /**
     * @return list<string>
     */
    public function via(AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(AnonymousNotifiable $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('account.profile.email_changed.subject'))
            ->line(__('account.profile.email_changed.notice', ['name' => Config::string('app.name'), 'email' => $this->newEmail]))
            ->line(__('account.profile.email_changed.ignore'))
            ->line(__('account.profile.email_changed.recover'));
    }
}
