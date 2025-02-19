<?php

use App\Actions\Attendance\SyncAttendance;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    SyncAttendance::dispatchSync();

//    return redirect('/admin');
})->name('home');


