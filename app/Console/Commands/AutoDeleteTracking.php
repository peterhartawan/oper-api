<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrackingTask;
use App\Models\TrackingAttendance;
use DB;
use Carbon\Carbon;

class AutoDeleteTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracking:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Delete for Tracking Task';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Delete tracking task 1 day expired
        // TrackingTask::whereDate('created_at', Carbon::yesterday())->forceDelete();

        //Delete tracking task 1 month expired
        TrackingTask::whereDate('created_at','<',Carbon::now()->subMonth(1)->toDateString())->forceDelete();

        //Delete tracking attendance 1 month expired
        TrackingAttendance::whereDate('created_at','<',Carbon::now()->subMonth(1)->toDateString())->forceDelete();
    }
}
