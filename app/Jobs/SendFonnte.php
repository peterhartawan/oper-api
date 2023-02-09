<?php

namespace App\Jobs;

use App\Services\FonnteServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFonnte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $phone, $message, $fonnte;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone, $message)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->fonnte = new FonnteServices();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->fonnte->sendMessage($this->phone, $this->message);
    }
}
