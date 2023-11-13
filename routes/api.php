<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{ AuthController };
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

Route::post('contact',[AuthController::class,'contact']);
Route::post('signup',[AuthController::class,'signup_process']);
Route::post('login',[AuthController::class,'login_process']);
Route::post('reset',[AuthController::class,'reset_password_process']);
Route::post('forgot',[AuthController::class,'forgot_process']);
Route::middleware('auth:api')->group(function(){
    Route::get('profile/{id}',[AuthController::class,'edit_profile']);
    Route::post('profile/update',[AuthController::class,'update_profile']);
    Route::get('logout',[AuthController::class,'logout']);
});
