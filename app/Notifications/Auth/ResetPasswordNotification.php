<?php

namespace App\Notifications\Auth;

use Closure;
use SensitiveParameter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The callback that should be used to create the reset password URL.
     *
     * @var (Closure(object, string): string)|null
     */
    public static ?Closure $createUrlCallback = null;

    /**
     * Create a notification instance.
     */
    public function __construct(
        #[SensitiveParameter]
        public readonly string $token
    ) {}

    /**
     * Get the notification's channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = static::$createUrlCallback
            ? call_user_func(static::$createUrlCallback, $notifiable, $this->token)
            : $this->resetUrl($notifiable);

        $expire = (int) config('auth.passwords.users.expire', 60);

        $name = property_exists($notifiable, 'name') && is_string($notifiable->name) && $notifiable->name !== ''
            ? $notifiable->name
            : 'User';

        return (new MailMessage)
            ->subject(__('email.password_reset.subject', ['app_name' => config('app.name')]))
            ->greeting(__('email.password_reset.greeting', ['name' => $name]))
            ->line(__('email.password_reset.body'))
            ->action(__('email.password_reset.action'), $url)
            ->line(__('email.password_reset.expiry', ['count' => $expire]))
            ->line(__('email.password_reset.warning'));
    }

    /**
     * Get the reset URL for the given notifiable.
     */
    protected function resetUrl(object $notifiable): string
    {
        $frontendUrl = (string) config('app.frontend_url', config('app.url'));
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? (string) $notifiable->getEmailForPasswordReset()
            : (string) ($notifiable->email ?? '');

        return rtrim($frontendUrl, '/') . '/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $email,
        ]);
    }

    /**
     * Set a custom callback for constructing the password reset URL.
     *
     * @param  Closure(object, string): string  $callback
     */
    public static function createUrlUsing(Closure $callback): void
    {
        static::$createUrlCallback = $callback;
    }
}
