<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;
    public $resetUrlBase; // URL dasar untuk halaman reset di frontend

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token, $resetUrlBase)
    {
        $this->token = $token;
        $this->resetUrlBase = $resetUrlBase;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Buat URL lengkap dengan token dan email
        // Email perlu di-encode jika mengandung karakter khusus
        $encodedEmail = urlencode($notifiable->getEmailForPasswordReset());
        $actionUrl = $this->resetUrlBase . '?token=' . $this->token . '&email=' . $encodedEmail;

        return (new MailMessage)
            ->subject('Permintaan Reset Password - Smart AC Control') // Ubah subjek email
            ->greeting('Halo, ' . $notifiable->name . '!') // Sapaan personal dengan nama user
            ->line('Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.') // Ubah baris pertama
            ->action('Reset Password Anda', $actionUrl) // Ubah teks tombol
            ->line('Link reset password ini akan kedaluwarsa dalam ' . config('auth.passwords.' . config('auth.defaults.passwords') . '.expire') . ' menit.') // Info kedaluwarsa
            ->line('Jika Anda tidak merasa melakukan permintaan ini, Anda dapat mengabaikan email ini dan tidak ada tindakan lebih lanjut yang diperlukan.') // Ubah baris terakhir
            ->salutation('Hormat kami,') // 5. Ubah "Regards,"
            ->line('Tim Smart Energy AC'); // 6. Ubah "Laravel"
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}