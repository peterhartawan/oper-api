<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailActivationRequest extends Notification implements ShouldQueue
{
    use Queueable;
    protected $token;
    protected $link;
    /**
    * Create a new notification instance.
    *
    * @return void
    */
    public function __construct($token,$link)
    {
        $this->token = $token;
        $this->link  = $link;
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
        $frontend = $this->link;
        $url = url($frontend . '/activation-email/' . $this->token);
        return (new MailMessage)
        ->line('You are receiving this email because we        received a change email request for your account.')
        ->action('Activation Link', url($url))
        ->line('If you did not request a change email request, no further action is required. ');
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
