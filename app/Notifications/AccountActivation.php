<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountActivation extends Notification implements ShouldQueue
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
        #frontend url
        #TODO get dynamically from database
        $frontend = $this->link;
        $url = url($frontend . '/create-password/' . $this->token);
        return (new MailMessage)
        ->line('Congratulations you are successfully registered, activate your account to start using Oper.')
            ->action('Activation link', url($url));
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
