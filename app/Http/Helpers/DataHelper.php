<?php

namespace App\Http\Helpers;

use Illuminate\Http\Request;
use App\Models\Dispatcher;
use Carbon\Carbon;
use App\Services\Response;
use App\Services\Validate;
use App\Constants\Constant;
use App\User;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use DB;

class DataHelper 
{
    public static function getDispatcherTotalAccount() {
        $users          = User::whereIn('idrole', [Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS])->whereIn('status', [Constant::STATUS_ACTIVE,Constant::STATUS_SUSPENDED])->count();
        $users_active   = User::whereIn('idrole', [Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS])->where('status', Constant::STATUS_ACTIVE)->count();
        $users_suspend  = User::whereIn('idrole', [Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS])->where('status', Constant::STATUS_SUSPENDED)->count();

        $response   = new \stdClass();
        $response->total_dispatcher   = $users;
        $response->active_account     = $users_active;
        $response->suspended_account  = $users_suspend;

        return $response;

    }

}