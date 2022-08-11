<?php

use Illuminate\Http\Request;
use App\Services\Response;
use App\Constants\Constant;
use App\Constants\RoleAccess;
use App\Exceptions\ApplicationException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('oauth/token', 'AccessTokenController@issueToken');
Route::post('login', 'AccessTokenController@issueToken');
Route::group([
    'middleware' => 'auth:api'
], function() {
    Route::get('logout', 'AuthController@logout');
});

Route::get('unauthorized', function(){
    throw new ApplicationException('errors.unauthorized');
})->name('login');

Route::group([
    'prefix' => 'password',
    'namespace' => 'Auth',
    'middleware' => 'api'
], function () {
    Route::post('create', 'PasswordResetController@create');
    Route::get('find/{token}', 'PasswordResetController@find');
    Route::post('reset', 'PasswordResetController@reset');
});

Route::group([
    'prefix' => 'otp',
    'middleware' => 'api'
], function () {
    Route::post('request', 'RequestOTPController@create');
    Route::post('check', 'RequestOTPController@check');
});

Route::get('user/activation-email/{token}', 'ChangeEmailController@find');

Route::get('roles', function(){
    return Response::success(RoleAccess::getConstants());
});

Route::group([
    'middleware' => 'auth:api'
], function () {

    Route::get('constant', function(){
        return Response::success(Constant::getConstants());
    });

    Route::group([
        'prefix' => 'role'
    ], function () {
        Route::get('list', 'RoleController@list');
        Route::get('access', 'RoleController@access');
    });

    Route::group([
        'prefix' => 'enterprise'
      ], function() {
        Route::get('type', 'ClientEnterpriseController@type');
        Route::post('suspend', 'ClientEnterpriseController@suspend');
        Route::post('activate', 'ClientEnterpriseController@activate');
        Route::post('resend-activation', 'ClientEnterpriseController@resendactivation');
        Route::post('admin', 'ClientEnterpriseController@admin');
    });

    Route::group([
        'prefix' => 'driver'
      ], function() {
        Route::put('/update-email/{id}', 'DriverController@updateEmail');
        Route::get('type', 'DriverController@type');
        Route::get('/available', 'DriverController@available');
        Route::get('/available-for-order', 'DriverController@available_for_order');
        Route::put('/assign-to-enterprise', 'DriverController@assign_to_enterprise');
        Route::post('/resend-pin', 'DriverController@resendpin');
        Route::post('/reporting', 'DriverController@orderdriver');
        Route::get('/total-account', 'DriverController@totalAccount');
    });

    Route::group([
        'prefix' => 'dispatcher'
      ], function() {
        Route::get('/available', 'DispatcherController@available');
        Route::get('/mdavailable', 'DispatcherController@mdavailable');
        Route::put('/assign-to-enterprise', 'DispatcherController@assign_to_enterprise');
        Route::put('/multi-to-enterprise', 'DispatcherController@multi_to_enterprise');
        Route::post('resend-activation', 'DispatcherController@resendactivation');
        Route::get('/total-account', 'DispatcherController@totalAccount');

    });

    Route::group([
        'prefix' => 'user'
      ], function() {
        Route::get('me', 'UserController@me');
        Route::put('change-password', 'UserController@change_password');
        Route::put('change-client/{id}', 'UserController@change_client');
        Route::put('change-vendor/{id}', 'UserController@change_vendor');
        Route::put('suspend/{id}', 'UserController@suspend');
        Route::put('activate/{id}', 'UserController@activate');
        Route::put('activate/{id}', 'UserController@activate');
        Route::delete('delete/{id}', 'UserController@deleteuser');
    });

    Route::group([
        'prefix' => 'order'
      ], function() {
        Route::get('/open', 'OrderController@open');
        Route::get('/inprogress', 'OrderController@inprogress');
        Route::get('/complete', 'OrderController@complete');
        Route::get('/cancel', 'OrderController@cancel');
        Route::get('/open/{id}', 'OrderController@show_open');
        Route::get('/inprogress/{id}', 'OrderController@show_inprogress');
        Route::get('/complete/{id}', 'OrderController@show_complete');
        Route::get('/cancel/{id}', 'OrderController@show_cancel');
        Route::put('change-status/{id}', 'OrderController@change_status');
        Route::put('assign', 'OrderController@assign');
        Route::post('task', 'OrderController@task');
        Route::post('task/skip', 'OrderController@skip_task');
        Route::post('assign', 'OrderController@assign');
        Route::get('/show/{trxId}', 'OrderController@showByTrxId');
        Route::get('/total-order-today', 'OrderController@totalordertoday');
        Route::get('/total-order-week', 'OrderController@totalorderweek');
        Route::get('/total-order-month', 'OrderController@totalordermonth');
        Route::get('/unavailable-dates', 'OrderController@unavailableDates');
        //FOR DRIVER
        Route::get('history/{id}', 'OrderController@history_detail');
        Route::get('history', 'OrderController@history');
        Route::get('templateorder', 'OrderController@template');
        //FOR DISPATCHER
        Route::post('cancelorder', 'OrderController@cancelorder');
    });

    Route::group([
        'prefix' => 'employee'
    ], function () {
        Route::post('/assign-task', 'EmployeeController@assign');
        Route::get('/show/{trxId}', 'OrderController@showByTrxId');
        Route::post('/resend-pin', 'EmployeeController@resendpin');
        //FOR vendor
        Route::post('cancelorder', 'EmployeeController@cancelorder');
        Route::get('/total-account', 'EmployeeController@totalAccount');
        Route::get('/total-order-today', 'EmployeeController@totalordertoday');
        Route::get('/total-order-week', 'EmployeeController@totalorderweek');
        Route::get('/total-order-month', 'EmployeeController@totalordermonth');
    });

    Route::group([
        'prefix' => 'task'
    ], function () {
        Route::get('/{id}', 'TaskController@show');
    });

    Route::group([
        'prefix' => 'employee'
    ], function () {
        Route::get('/task/inprogress', 'EmployeeController@inprogress');
        Route::get('/task/complete', 'EmployeeController@complete');
        Route::get('/task/inprogress/{id}', 'EmployeeController@showInprogress');
        Route::get('/task/complete/{id}', 'EmployeeController@showComplete');
        Route::post('/reporting', 'EmployeeController@orderReporting');
    });

    Route::group([
        'prefix' => 'attendance'
      ], function() {
        Route::get('/', 'AttendanceController@last_status');
        Route::get('/reporting', 'AttendanceController@reporting');
        Route::post('/clock-in', 'AttendanceController@clock_in');
        Route::post('/clock-out', 'AttendanceController@clock_out');
        Route::get('/{id}', 'AttendanceController@show');
        Route::delete('/{id}', 'AttendanceController@destroy');
    });

    Route::group([
        'prefix' => 'pages'
      ], function() {
        Route::get('/{slug}', 'StaticContentController@slug')->where('slug', '[A-Za-z\-]+');
        Route::get('/{id}', 'StaticContentController@show')->where('idstatic_content', '[0-9]+');
    });

    Route::group([
        'prefix' => 'tracking'
      ], function() {
        Route::get('/task', 'TrackingController@listTrackingTask');
        Route::get('/taskwdriver', 'TrackingController@listTrackingTaskWithDriver');
        Route::get('/attendance', 'TrackingController@listTrackingAttendance');
    });

    Route::post('import/driverMukti', 'ImportController@importDriver');
    Route::post('import/driver', 'ImportController@importDriverFormatted');
    Route::post('import/vendor', 'ImportController@importVendorFormatted');
    Route::get('export/order', 'ExportController@exportExcel');
    Route::post('import/order', 'ImportController@importOrder');

    Route::group([
        'prefix' => 'vehicle'
      ], function() {
          Route::apiResources([
            'brand'     => 'VehicleBrandController',
            'type'      => 'VehicleTypeController',
      ]);
    });

    Route::group([
        'prefix' => 'notification'
      ], function() {
          Route::post('/updatetoken', 'MobileNotificationController@mobilenotification');
          Route::post('/markasread', 'WebNotificationController@markasread');
    });

    Route::group([
        'prefix' => 'version'
      ], function() {
          Route::get('/check', 'MobileCheckUpdateController@checkVersion');
    });

    Route::get('notification', 'WebNotificationController@webnotification');

    Route::group([
        'prefix' => 'test',
    ], function () {
          Route::get('/index', 'TestingController@index');
    });

    Route::group([
        'prefix' => 'template',
    ], function () {
          Route::post('/template-report', 'TaskTemplateController@tasktemplatereporting');
    });

    Route::group([
        'prefix' => 'vendor'
    ], function () {
        Route::post('suspend', 'VendorController@suspend');
        Route::put('activate', 'VendorController@activate');
        Route::post('resend-activation', 'VendorController@resendactivation');
        Route::post('admin', 'VendorController@admin');
    });

    Route::group([
        'prefix' => 'dashboard',
    ], function () {
          Route::get('/grafik', 'DashboardController@grafik');
    });

    Route::group([
        'prefix' => 'driver-requests'
    ], function() {
        Route::get('/', 'DriverRequestController@index');
        Route::get('/{id}', 'DriverRequestController@show');
        Route::post('/', 'DriverRequestController@store');
        Route::post('/{id}', 'DriverRequestController@update');
    });

    Route::group([
        'prefix' => 'order-b2c'
    ], function() {
        Route::get('/{link}', 'OrderB2CController@showByLink');
        Route::get('/latest/{phone}', 'OrderB2CController@getLatest');
        Route::get('/form/{phone}', 'OrderB2CController@getFormData');
        Route::post('/cancel', 'OrderB2CController@cancelOrder');
        Route::get('/invoice/{link}', 'OrderB2CController@getInvoiceData');
    });

    Route::group([
        'prefix' => 'rating-b2c'
    ], function() {
        Route::post('/', 'RatingB2CController@store');
        Route::get('/driver/{driver_id}', 'RatingB2CController@getRatingByDriverId');
        Route::get('/link/{link}', 'RatingB2CController@getRatingByLink');
    });

    Route::group([
        'prefix' => 'otp'
    ], function() {
        Route::post('/', 'OTPB2CController@store');
        Route::post('/verify', 'OTPB2CController@verify');
        Route::post('/phone', 'OTPB2CController@isPhoneSucceedOTP');
    });

    Route::group([
        'prefix' => 'customer-b2c'
    ], function() {
        Route::get('/{phone}', 'CustomerB2CController@getCustomerByPhone');
    });

    Route::group([
        'prefix' => 'coupon'
    ], function() {
        Route::post('/claim', 'KuponController@claim');
    });

    Route::apiResources([
        'user'              => 'UserController',
        'enterprise'        => 'ClientEnterpriseController',
        'task-template'     => 'TaskTemplateController',
        'driver'            => 'DriverController',
        'vendor'            => 'VendorController',
        'pages'             => 'StaticContentController',
        'vehicles'          => 'VehiclesController',
        'places'            => 'PlacesController',
        'inspector'         => 'InspectorController',
        'order'             => 'OrderController',
        'dispatcher'        => 'DispatcherController',
        'faq'               => 'FaqController',
        'dashboard'         => 'DashboardController',
        'employee'          => 'EmployeeController',
        'employeeposition'  => 'EmployeePositionController',
        'tracking'          => 'TrackingController',
        'eventlog'          => 'EventLogController',
        'driver-requests'   => 'DriverRequestController',
        'order-b2c'         => 'OrderB2CController',
        'rating-b2c'        => 'RatingB2CController',
        'otp'               => 'OTPB2CController',
        'customer-b2c'      => 'CustomerB2CController'
    ]);
});
