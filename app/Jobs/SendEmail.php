<?php

namespace App\Jobs;

use App\Mail\mailGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $params;
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params=array())
    {
        $this->params=$params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
            $message = new mailGenerator($this->params['subject'],$this->params['view'],$this->params['params']);
            Mail::to($this->params['recipient'])->send($message);       
       
    }
}
