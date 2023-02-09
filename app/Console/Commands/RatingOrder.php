<?php

namespace App\Console\Commands;

use App\Constants\Constant;
use App\Models\B2C\OrderB2C;
use App\Services\FonnteServices;
use App\Services\QontakHandler;
use Illuminate\Console\Command;
use DB;

class RatingOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:rating';

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
        $orders = OrderB2C::where('status', 4)
            ->select(
                'updated_at',
                DB::Raw("@timenow:=(NOW()) as time_now"),
                DB::Raw("TIMESTAMPDIFF(MINUTE, updated_at, @timenow) as time_diff"),
                'orders.*'
            )
            ->with(['customer'])
            ->get();

        // $qontakHandle = new QontakHandler();
        $fonnteServices = new FonnteServices();

        foreach($orders as $order){
            if($order->time_diff == 4){
                $phone = $order->customer->phone;
                $link = $order->link;
                // $qontakHandle->sendMessage(
                //     "62".$phone,
                //     "Rating",
                //     Constant::QONTAK_TEMPLATE_RATING,
                //     [
                //         [
                //             "key"=> "1",
                //             "value"=> "link",
                //             "value_text"=> "https://driver.oper.co.id/rating/" . $link
                //         ]
                //     ]
                // );
                $fonnteServices->sendMessage(
                    "62".$phone,
                    "Bagaimana perjalanan Anda bersama OPER? Beri rating Anda di sini https://driver.oper.co.id/rating/" . $link . ". Penilaian Anda sangat berarti untuk peningkatan kualitas layanan kami."
                );
            }
        }
    }
}
