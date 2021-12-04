<?php
namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use app\Model\User;


class sendMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $name;
    protected $email;
    protected $link;

    public function __construct($name,$email,$link)
    {
       $this->name = $name;
       $this->email= $email;
       $this->link = $link;
    }

    public function build()
    {
        
        return $this->view('mail.email',
            ['name'=>$this->name,
            'email'=>$this->email,
            'remember_token'=>$this->link]);
    }

}

