<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class MobileResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your ZaddyExpress password reset code')
            ->line('You requested a password reset.')
            ->line('Your reset code is: ' . $this->token)
            ->line('Enter this code in the app along with your new password.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
