<?php
namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use app\Model\User;


class sendAnnouncement extends Mailable
{
    use Queueable, SerializesModels;

    protected $title;
    protected $body;

    public function __construct($title,$body)
    {
       $this->title = $title;
       $this->body  = $body;
    }

    public function build()
    {
        // $user = User::where('id',$this->id)->first();
        return $this->view('mail.announcementmail',
            ['title'=>$this->title,
            'body'=>$this->body,
            
            ]);
    }

}

