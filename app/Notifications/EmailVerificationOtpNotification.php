<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    use Queueable;

    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Simulasi AKI/CAPEX] Kode Verifikasi Email Anda: ' . $this->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Terima kasih telah mendaftar di sistem Simulasi AKI/CAPEX Telkom.')
            ->line('Gunakan kode verifikasi berikut untuk memvalidasi alamat email Anda:')
            ->line('**' . $this->code . '**')
            ->line('Kode verifikasi ini berlaku selama 15 menit.')
            ->line('Jika Anda tidak merasa melakukan pendaftaran akun ini, Anda dapat mengabaikan email ini.')
            ->salutation('Salam hormat,' . "\n" . 'Tim Simulasi AKI/CAPEX Telkom');
    }
}
