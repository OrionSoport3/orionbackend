<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ServiceController;

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
    Route::post('logout', [AuthenticationController::class, 'logout']);
});

Route::middleware(['jwt'])->group(function () {
    Route::post('get', [AdminAuthController::class, 'fetchAllInfo']);
    Route::post('activities', [AdminAuthController::class, 'postActivitie']);
    Route::post('fotos', [AdminAuthController::class, 'fetchFotos']);
    Route::post('publish_carro', [AdminAuthController::class, 'guardarFoto']);
    Route::post('new_activity', [AdminAuthController::class, 'postActivitie']);
    Route::post('get_activities', [AdminAuthController::class, 'fetchActivities']); 

    Route::post('get_service', [ServiceController::class, 'getService']); 
    Route::post('post_carpeta', [ServiceController::class, 'postFile']); 
    Route::post('get_carpetas', [ServiceController::class, 'getCarpetas']); 

    Route::post('subir_archivo', [FileController::class, 'upload']); 
});

