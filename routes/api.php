<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticationController;


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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('auth')->group(function(){
    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::post('token', [AuthenticationController::class, 'createToken']);
    Route::post('update',[AuthenticationController::class, 'update_password']);
});

Route::middleware(['auth.jwt'])->group(function () {
    Route::post('get', [AdminAuthController::class, 'getUsers']);
    Route::post('companies', [AdminAuthController::class, 'companies']);
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::post('activities', [AdminAuthController::class, 'postActivitie']);
});
