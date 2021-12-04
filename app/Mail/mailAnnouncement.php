<?php
/**
 * Created by PhpStorm.
 */
namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class mailAnnouncement extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct($subject,$view,$params=array(),$attach)
    {
        $this->subjectMail = $subject;
        $this->viewMail= $view;
        $this->paramsMail = $params;
        $this->attach= $attach;
    }
    public function build()
    {
        if ($this->attach == NULL){
            return $this->subject($this->subjectMail)
            ->view($this->viewMail,$this->paramsMail);
        }
        return $this->subject($this->subjectMail)
            ->view($this->viewMail,$this->paramsMail)->attachFromStorage($this->attach);
    }
}