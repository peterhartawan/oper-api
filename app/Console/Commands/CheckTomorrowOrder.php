<?php

namespace App\Console\Commands;

use App\Constants\Constant;
use App\Models\B2C\OrderB2C;
use App\Models\Order;
use App\Services\FonnteServices;
use App\Services\QontakHandler;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckTomorrowOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:cft';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check order for tomorrow, if exists blast WA';

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

        // Check if there's any order for tomorrow
        $orders = Order::whereDate('booking_time', '=', Carbon::today()->addDays(1))
            ->where('client_enterprise_identerprise', env("B2C_IDENTERPRISE"))
            ->get();

        if(!empty($orders)){
            foreach($orders as $order){
                $order_b2c = OrderB2C::where('oper_task_order_id', $order->idorder)
                    ->first();
                // For now test blast wa to myself
                // $qontakHandle = new QontakHandler();
                $fonnteServices = new FonnteServices();
                // $qontakHandle->sendMessage(
                //     "62" . $order->user_phonenumber,
                //     "Tomorrow Reminder",
                //     Constant::QONTAK_TEMPLATE_TOMORROW_REMINDER,
                //     [
                //         [
                //             "key"=> "1",
                //             "value"=> "name",
                //             "value_text"=> $order->user_fullname,
                //         ],
                //         [
                //             "key"=> "2",
                //             "value"=> "link",
                //             "value_text"=> "https://driver.oper.co.id/dashboard/" . $order_b2c->link
                //         ],
                //     ]
                // );
                $fonnteServices->sendMessage(
                    "62" . $order->user_phonenumber,
                    "Selamat malam " . $order->user_fullname . ", mau ngingetin nih, Anda memiliki order untuk besok. Cek lebih lengkap di sini! https://driver.oper.co.id/dashboard/" . $order_b2c->link
                );
                // Log::info($order->user_phonenumber);
            }
        }
    }
}
