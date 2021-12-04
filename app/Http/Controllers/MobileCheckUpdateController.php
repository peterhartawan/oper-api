<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Response;
use App\Constants\Constant;
use Carbon\Carbon;
use App\Models\MobileCheckUpdate;
use App\Exceptions\ApplicationException;

class MobileCheckUpdateController extends Controller
{
    public function checkVersion()
    {
        $result = MobileCheckUpdate::where('device_type', 'android')->first();

        if (empty($result)) {
            throw new ApplicationException("errors.mobile_version_invalid");
        }
        
        return Response::success($result);
    }
}
