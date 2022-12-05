<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        // $schedule->command('tracking:delete')
        // ->dailyAt('00:05')
        // ->timezone('Asia/Jakarta');
        // $schedule->call(function(){
        //     info('called every minute');
        // })->everyMinute()->runInBackground();

        // Check order is ending
        $schedule->command('order:ce')->everyMinute()->runInBackground();

        // Check order rating
        $schedule->command('order:rating')->everyMinute()->runInBackground();

        // Check order for tomorrow
        $schedule->command('order:cft')->dailyAt('20:00')->runInBackground();

        // Check weekly for monthly b2c
        $schedule->command('monthly:create')->weeklyOn(2, '12:39')->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
