<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitation extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
        $this->onQueue('notifications');
    }

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(User $notifiable, string $channel): bool
    {
        $user = $notifiable->fresh();

        return $user !== null && $user->invitationIsValid() && hash_equals($user->invitation_token, hash('sha256', $this->token));
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Zaproszenie do zespołu — ŁapaRazem')
            ->greeting('Cześć, '.$notifiable->name.'!')
            ->line('Zapraszamy do zespołu schroniska '.$notifiable->organization->shelter_name.'.')
            ->line('Twoja rola: '.User::TEAM_ROLES[$notifiable->teamRole()].'.')
            ->line('Ustaw własne hasło, aby aktywować konto. Link jest ważny przez 7 dni.')
            ->action('Przyjmij zaproszenie', route('invitations.show', ['token' => $this->token]))
            ->line('Jeśli nie oczekujesz tego zaproszenia, zignoruj tę wiadomość.')
            ->salutation('Zespół ŁapaRazem');
    }
}
