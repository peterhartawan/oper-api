<?php

namespace App\Console\Commands;

use App\Constants\Constant;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\OrderB2C;
use App\Services\QontakHandler;
use Illuminate\Console\Command;
use DB;

class CheckEndingOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:ce';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // Log::info("CHECK ENDING ORDER CALLED");
        $orders = OrderB2C::whereNotNull('time_start')
            ->whereNull('time_end')
            ->select(
                // 'orders.time_start',
                DB::Raw("@estend:=(IF(service_type_id = 0, DATE_ADD(time_start, INTERVAL 480 MINUTE),
                  IF(service_type_id = 1,
                   DATE_ADD(time_start, INTERVAL 720 MINUTE),
                   DATE_ADD(time_start, INTERVAL 240 MINUTE)
                  )
                 )) as est_end"),
                DB::Raw("@timenow:=(NOW()) as time_now"),
                DB::Raw("TIMESTAMPDIFF(MINUTE, @timenow, @estend) as time_diff"),
                DB::Raw("IF(service_type_id = 0, '8 Jam',
                 IF(service_type_id = 1,
                  '12 Jam',
                  '4 Jam'
                 )
                ) as pk_name"),
                'orders.*',
            )
            ->with(['customer'])
            ->get();
        // Log::info($orders);
        $qontakHandler = new QontakHandler();

        foreach($orders as $order){
            // Reminder ending
            // Log::info("Order dengan ID " . $order->id . " diff : " . $order->time_diff);
            if($order->time_diff == 29){
                // Log::info("Order dengan ID " . $order->id . " 30 min, tembak WA");
                $phone = $order->customer->phone;
                $paket = 8;
                $order->service_type_id == 1 ? $paket = 12 : $paket = 4;
                $qontakHandler->sendMessage(
                    "62".$phone,
                    "Reminder 30min",
                    Constant::QONTAK_TEMPLATE_REMINDER_30MIN,
                    [
                        [
                            "key"=> "1",
                            "value"=> "paket",
                            "value_text"=> $paket
                        ],
                    ]
                );
            }
            // Reminder overtime
            if($order->time_diff == -1){
                // Log::info("Order dengan ID " . $order->id . " masuk OVERTIME, tembak WA");
                $fullname = $order->customer->fullname;
                $phone = $order->customer->phone;
                $paket = 8;
                $order->service_type_id == 1 ? $paket = 12 : $paket = 4;
                $qontakHandler->sendMessage(
                    "62".$phone,
                    "Reminder 30min",
                    Constant::QONTAK_TEMPLATE_REMINDER_OVERTIME,
                    [
                        [
                            "key"=> "1",
                            "value"=> "fullname",
                            "value_text"=> $fullname
                        ],
                        [
                            "key"=> "2",
                            "value"=> "paket",
                            "value_text"=> $paket
                        ],
                    ]
                );
            }
        }
    }
}
