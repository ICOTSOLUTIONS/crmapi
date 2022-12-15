<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{ Admin };
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

Route::post('contact',[Admin\AuthController::class,'contact']);
Route::post('signup',[Admin\AuthController::class,'signup_process']);
Route::post('login',[Admin\AuthController::class,'login_process']);
Route::post('reset',[Admin\AuthController::class,'reset_password_process']);
Route::post('forgot',[Admin\AuthController::class,'forgot_process']);
Route::middleware('auth:api')->group(function(){
    Route::get('profile/{id}',[Admin\AuthController::class,'edit_profile']);
    Route::post('profile/update',[Admin\AuthController::class,'update_profile']);
    Route::get('logout',[Admin\AuthController::class,'logout']);
});
