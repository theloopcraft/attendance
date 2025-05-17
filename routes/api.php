<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Rats\Zkteco\Lib\ZKTeco;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('attendance', function () {
    $zk = new ZKTeco('192.168.1.251', 4370);
    $zk->connect();
    $zk->disableDevice();
    dd($zk->getAttendance(49));
    $zk->disconnect();
});