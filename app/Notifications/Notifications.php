<?php

namespace App\Notifications;

use App\Models\EmployeeAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Notifications extends Notification
{
    use Queueable;


    public $message;
    public $redirect;
    public $type;
    public $audience;

    /**
     * Create a new notification instance.
     */
    public function __construct($type, $message, $redirect, $audience)
    {
        $this->type = $type;
        $this->message = $message;
        $this->redirect = $redirect;
        $this->audience = $audience;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Send email only for employees who enabled it in profile settings
        $effectiveEmail = $notifiable instanceof EmployeeAccount
            ? ($notifiable->company_email ?: $notifiable->email)
            : $notifiable->email;
        if (
            $this->audience === 'employee'
            && $notifiable instanceof EmployeeAccount
            && (bool) ($notifiable->email_notifications_enabled ?? false)
            && !empty($effectiveEmail)
        ) {
            $allowedDomains = (array) config('notifications.email_allowed_domains', []);

            // If allowlist is set, only send to allowed domains (e.g., @novulutions.com)
            if (empty($allowedDomains) || $this->isEmailAllowed((string) $effectiveEmail, $allowedDomains)) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    private function isEmailAllowed(string $email, array $allowedDomains): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || !str_contains($email, '@')) {
            return false;
        }

        $domain = strtolower(trim(substr($email, strrpos($email, '@') + 1)));
        if ($domain === '') {
            return false;
        }

        foreach ($allowedDomains as $allowed) {
            $allowed = strtolower(trim((string) $allowed));
            if ($allowed === '') continue;

            // Exact match or subdomain match
            if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $plainMessage = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->message)));
        $actionUrl = $this->redirect ?: url('/');

        $mail = (new MailMessage)
            ->subject('HRIS Notification');

        if ($this->audience === 'employee' && $notifiable instanceof EmployeeAccount) {
            $personal = $notifiable->personal ?? $notifiable->load('personal')->personal;
            $firstname = $personal ? trim($personal->firstname ?? '') : '';
            $greeting = $firstname !== '' ? 'Hello ' . $firstname . ',' : 'Hello,';
            $mail->greeting($greeting);
        }

        $mail->line($plainMessage !== '' ? $plainMessage : 'You have a new notification.')
            ->action('View', $actionUrl)
            ->line('If you did not expect this, you can ignore this email.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'redirect' => $this->redirect,
            'audience' => $this->audience
        ];
    }

}
