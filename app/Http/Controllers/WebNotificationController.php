<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Services\Validate;
use App\Constants\Constant;
use App\Notifications\AccountActivation;
use App\Notifications\NotificationOrderWeb;
use DB;
use App\User;
use App\PasswordReset;
use App\Models\MobileNotification;
use App\Exceptions\ApplicationException;
use Illuminate\Notifications\Notifiable;

class WebNotificationController extends Controller
{
    
    public function webnotification(Request $request)
    {
        $userid   = auth()->guard('api')->user()->id;
        $user     = User::find($userid);

        $i=0;
        foreach ($user->unreadNotifications as $notification) {
            $i++;
        }

        return Response::success(['total' => $i]);
    }

    public function markasread(Request $request)
    {
        $userid   = auth()->guard('api')->user()->id;
        $user     = User::find($userid);

        if ($user->unreadNotifications()->update(['read_at' => now()])) {
            return Response::success(['id' => $userid]);
        } else {
            throw new ApplicationException("notificationsweb.failure_delete_notif", ['id' => $userid]);
        }
    }


}