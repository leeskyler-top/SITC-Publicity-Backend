<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::post("/auth/login", [AuthController::class, 'login']);
Route::middleware("auth:api")->group(function () {
    Route::delete("/auth/logout", [AuthController::class, 'logout']);
    Route::prefix("/user")->group(function () {
        Route::post("/pwd/change", [UserController::class, 'changePwd']);
    });
    Route::apiResource("user", UserController::class)->only(['show']);
});
Route::middleware("admin")->group(function () {
    Route::prefix("/user")->group(function () {
        Route::post("/batch/add", [UserController::class, 'batchStore']);
        Route::get("/pwd/reset/{id}", [UserController::class, 'resetPwd']);
    });
    Route::apiResource("user", UserController::class)->only(['index', 'store', 'destroy', 'update']);
});
