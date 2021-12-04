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

class EventLog 
{
    public static function insertLog($email,$reason,$dataraw,$model) {
        
        $inser_log = DB::connection('mysql2')
                    ->insert('insert into event_log (id, event_name, event_module, data_raw, created_by,created_at) values (?, ?, ?, ?, ?, ?)',[null,$reason.' '.$email, $model, $dataraw, auth()->guard('api')->user()->id,Carbon::now()]);

        return $inser_log;

    }

}