<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\User;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Models\TrackingAttendance;
use App\Constants\Constant;

class TestingController extends Controller
{
    public function __construct()
    {

        if (in_array(env('APP_ENV'), [Constant::ENV_STAGING, Constant::ENV_PRODUCTION])) {
            Response::error("You don't have power here", 4333, 403);
        }
    }

    public function index(Request $request)
    {
        //Delete tracking attendance 1 month expired
        $tracking = TrackingAttendance::whereMonth('created_at', Carbon::now()->subMonth()->month)->get();

        //2 days ago
        $tracking = TrackingAttendance::whereDate('created_at', Carbon::now()->subDays(2)->toDateString())->get();

        // dd(Carbon::now()->subDays(2));
        // return Response::success($tracking);


        $pass1 = rand(12345678,45678910);
        $pass = \bcrypt($pass1);

        return Response::success([$pass1, $pass]);
    }
}
