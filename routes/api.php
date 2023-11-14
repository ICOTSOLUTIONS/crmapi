<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AttendanceController, AuthController, EmployeeController};
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

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('contact',[AuthController::class,'contact']);
Route::middleware('auth:api')->group(function(){
    Route::get('/current-user', [AuthController::class, 'currentUser']);
    Route::post('/profile-update', [AuthController::class, 'profileUpdate']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('logout',[AuthController::class,'logout']);

    Route::apiResources([
        'employee' => EmployeeController::class,
        'attendance' => AttendanceController::class,
    ]);
    Route::get('/status/{id}', [EmployeeController::class, 'status_change']);

});
