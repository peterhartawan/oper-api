<?php

namespace App\Http\Controllers;

use App\Models\DriverBlastReply;
use App\Services\FonnteServices;
use App\User;
use Illuminate\Http\Request;
use DB;
use Exception;
use Log;

class WebhookController extends Controller
{
    public function handle_fonnte(Request $request)
    {
        // Get sender information
        $sender_phonenumber = $request->sender;
        $sender_message = $request->message;
        Log::info($sender_phonenumber . ' sent ' . $sender_message);

        $formatted_phone = "0" . substr($sender_phonenumber, 2);

        // Validate driver on db
        $driver = User::select('id', 'name', 'phonenumber')
            ->where('phonenumber', $formatted_phone)
            ->where('idrole', 7)
            ->where('status', 1)
            ->where('vendor_idvendor', 37)
            ->whereNotIn('phonenumber', ['3674030807770007', '3175081210850001', '081388090588'])
            ->has('attendance', '>', 30)
            ->with(['driver_profile:users_id,address'])
            ->first();

        // If driver exists, update field and blast back
        if (!empty($driver)) {
            // Check if driver already replied
            $reply = DriverBlastReply::where('phonenumber', $formatted_phone)->first();

            // If not insert to the table
            if (empty($reply)) {
                // Check reply format
                if ($sender_message == 'Ya') {

                    DB::beginTransaction();

                    try {
                        // Insert to table
                        DriverBlastReply::create([
                            'name'          => $driver->name,
                            'phonenumber'   => $driver->phonenumber,
                            'address'       => $driver->driver_profile->address
                        ]);

                        DB::commit();

                        // Then reply that we received the information very well
                        $fonnte = new FonnteServices();

                        $fonnte->sendMessage(
                            $sender_phonenumber,
                            "Terima kasih atas konfirmasi anda. Respon anda sudah tercatat di sistem kami."
                        );

                    } catch (Exception $e) {
                        Log::error($e);
                        DB::rollback();
                    }
                }
            }
        }
    }
}
