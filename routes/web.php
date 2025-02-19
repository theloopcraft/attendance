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

    $allData = [];
    $attendances = Attendance::query()->latest()->first();
    $startAt = Carbon::now()->startOfDay()->toDateTimeString();
    $endAt = Carbon::now()->endOfDay()->toDateTimeString();

    if ($attendances) {
        $startAt = Carbon::parse($attendances->action_at)->startOfDay()->toDateTimeString();
        $endAt = Carbon::parse($attendances->action_at)->endOfDay()->toDateTimeString();
    }

    do {
        $response = Http::baseUrl('http://192.168.1.155')
            ->withToken('de70f6cb421a5a62a478d448bdddc9a95cacc9ab', 'Token')
            ->acceptJson()
            ->get('iclock/api/transactions/', [
                'start_time' => $startAt,
                'end_time' => $endAt,
                'page' => 1,
                'page_size' => 100,
            ]);

        if (!$response->successful()) {
            Log::error($response->json());
            dd($response->json());
        }

        $data = $response->json();
        $allData = array_merge($allData, $data['data']);

        // Move to the next page if available
        $nextUrl = $data['next'];
        if ($nextUrl) {
            $queryParams = parse_url($nextUrl, PHP_URL_QUERY);
            parse_str($queryParams, $params);
        }

    } while ($nextUrl !== null);

    dd($allData);

    $data = SyncAttendance::run();


    return redirect('/admin');
})->name('home');


