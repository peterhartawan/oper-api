<?php

use App\Services\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Response::success(trans("messages.welcome"));
});

// Route::get('/testinvoice/{ot_order_id}', 'MailTestController@getMailFromOrder');
