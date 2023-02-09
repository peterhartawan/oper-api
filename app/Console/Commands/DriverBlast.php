<?php

namespace App\Console\Commands;

use App\Jobs\SendFonnte;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Log;

class DriverBlast extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver:blast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Blast old drivers';

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
        Log::info('Start blasting drivers');

        // Select drivers from oper's b2b, that reached 30 days of attendance
        $drivers = User::select('id', 'name', 'phonenumber')
            ->where('idrole', 7)
            ->where('status', 1)
            ->where('vendor_idvendor', 37)
            ->whereNotIn('phonenumber', ['3674030807770007', '3175081210850001', '081388090588'])
            ->has('attendance', '>', 30)
            ->pluck('phonenumber')->toArray();

        // Log::info($drivers);

        // Iterate to send messages
        foreach ($drivers as $driver) {
            // The reverse of webhook phone number formatting
            $formatted_phone = "62" . substr($driver->phonenumber, 1);

            $message =
                "Halo, kami dari OPER Indonesia ingin mengucapkan banyak terima kasih dan penghargaan setinggi-tingginya kepada anda sebagai mitra driver kami.\n\n" .

                "Dikarenakan adanya lonjakan order yang tinggi, kami sedang membuka kesempatan sebesar-besarnya kepada mitra driver kami untuk memberikan konfirmasi untuk menerima order dari kami.\n\n" .

                "Balas \"Ya\" untuk mengkonfirmasi, Balas \"Tidak\" atau abaikan jika anda tidak berkenan.\n\n" .

                "Mohon balas sesuai format yang diberikan diatas agar terbaca secara otomatis oleh sistem kami, sekian dan terima kasih.";

            $delay = rand(8, 15);

            try {
                SendFonnte::dispatch(
                    $formatted_phone,
                    $message
                );

                // Successful
                Log::info("Driver " . $driver->name . " with phonenumber " . $driver->phonenumber . " blasted successfully with given " . $delay . " seconds delay.");

            } catch (Exception $e) {

                // Failed
                Log::alert("Failed to blast driver " . $driver->name . " with phonenumber " . $driver->phonenumber);
                Log::error($e);
            }

            sleep($delay);
        }
    }
}
