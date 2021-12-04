<?php
namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use app\Model\User;


class sendNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $name;
    protected $email;
    protected $roles;

    public function __construct($name,$email,$roles)
    {
       $this->name = $name;
       $this->email= $email;
       $this->roles = $roles;
    }

    public function build()
    {
        
        return $this->view('mail.notificationmail',
            ['name'=>$this->name,
            'email'=>$this->email,
            'roles'=>$this->roles
            ]);
    }

}

