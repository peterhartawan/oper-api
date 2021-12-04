<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message;
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
        if(is_array($this->message)){
            $content = (new MailMessage);
            foreach ($this->message as $key => $value) {
                if($key == 'greeting'){                   
                    $content->greeting($value);
                }else{
                    if(is_array($value)){
                        foreach ($value as $key2 => $val) {
                            $content->line($key2. ' : '.$val);
                        }
                    }else{
                        $content->line($value);
                    }                   
                }
            }
            return $content;            
        }else{        
            return (new MailMessage)
                    ->line($this->message);
        }
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

