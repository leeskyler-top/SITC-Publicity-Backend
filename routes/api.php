<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EquipmentController;
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
    Route::prefix("/equipment")->group(function () {
        Route::get("/unassigned", [EquipmentController::class, 'indexUnassignedEquipments']);
        Route::post("/apply", [EquipmentController::class, 'equipmentApply']);
        Route::get("/my/{status}", [EquipmentController::class, 'showMyEquipment']);
        Route::post("/back/{equipment_id}", [EquipmentController::class, 'back']);
        Route::post("/report/{equipment_id}", [EquipmentController::class, 'reportEquipment']);
        Route::post("/delay-apply/{equipment_rent_application_id}", [EquipmentController::class, 'delayApply']);
    });
});
Route::middleware("admin")->group(function () {
    Route::prefix("/user")->group(function () {
        Route::post("/batch/add", [UserController::class, 'batchStore']);
        Route::get("/pwd/reset/{id}", [UserController::class, 'resetPwd']);
    });
    Route::apiResource("user", UserController::class)->only(['index', 'store', 'destroy', 'update']);
    Route::prefix("/equipment")->group(function () {
        Route::post("/batch/add", [EquipmentController::class, 'batchStore']);
        Route::prefix("/list")->group(function () {
            Route::get("/rent-history", [EquipmentController::class, 'indexRentHistory']);
            Route::get("/report/{status}", [EquipmentController::class, 'indexReports']);
            Route::get("/application/{status}", [EquipmentController::class, 'indexApplicationList']);
            Route::get("/delay-application/{status}", [EquipmentController::class, 'indexDelayApplication']);
            Route::get("/delay-application/search/{equipment_rent_application_id}", [EquipmentController::class, 'indexAllDelayApplicationByERID']);
        });
        Route::prefix("/audit")->group(function () {
            Route::prefix("/rent-application")->group(function () {
                Route::get("/agree/{equipment_rent_application_id}", [EquipmentController::class, 'agreeApplication']);
                Route::get("/reject/{equipment_rent_application_id}", [EquipmentController::class, 'rejectApplication']);
            });
            Route::prefix("/delay-application")->group(function () {
                Route::get("/agree/{equipment_delay_id}", [EquipmentController::class, 'agreeEquipmentDelayApplication']);
                Route::get("/reject/{equipment_delay_id}", [EquipmentController::class, 'rejectEquipmentDelayApplication']);
            });
        });
    });
    Route::apiResource("equipment", EquipmentController::class);
});
