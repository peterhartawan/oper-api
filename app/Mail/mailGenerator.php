<?php
/**
 * Created by PhpStorm.
 */
namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class mailGenerator extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct($subject,$view,$params=array())
    {
        $this->subjectMail = $subject;
        $this->viewMail= $view;
        $this->paramsMail = $params;
       
    }
    public function build()
    {
        
        return $this->subject($this->subjectMail)
        ->view($this->viewMail,$this->paramsMail);
        
    }
}