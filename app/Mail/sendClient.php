<?php
namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use app\Model\Project;


class sendClient extends Mailable
{
    use Queueable, SerializesModels;

    protected $title;
    protected $email;


    public function __construct($title,$email,$projectid, $link)
    {
       $this->title = $title;
       $this->email= $email;
       $this->projectid = $projectid;
       $this->link = $link;
    }

    public function build()
    {
        // $user = User::where('id',$this->id)->first();
        return $this->view('mail.client',
            ['title'=>$this->title,
            'email'=>$this->email,
            'projectid'=>$this->projectid,
            'link'=>$this->link]);
    }

}




