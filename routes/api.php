<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApiHistoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EquipmentController;
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

Route::options('/{any}', function () {
    return response()->json(null,204);
})->where('any', '.*');

Route::get('/captcha', [AuthController::class, 'generateCaptcha']);
Route::post("/auth/login", [AuthController::class, 'login']);
Route::middleware("auth:api")->group(function () {
    Route::get('/files/images/{type}/{filename}', [FileController::class, 'normal']);
    Route::delete("/auth/logout", [AuthController::class, 'logout']);
    Route::prefix("/user")->group(function () {
        Route::post("/pwd/change", [UserController::class, 'changePwd']);
    });
    Route::apiResource("user", UserController::class)->only(['show']);
    Route::prefix("/equipment")->group(function () {
        Route::get("/unassigned", [EquipmentController::class, 'indexUnassignedEquipments']);
        Route::post("/apply", [EquipmentController::class, 'equipmentApply']);
        Route::get("/my/{status}", [EquipmentController::class, 'showMyEquipment']);
        Route::post("/back/{equipment_rent_application_id}", [EquipmentController::class, 'back']);
        Route::post("/report/{equipment_rent_application_id}", [EquipmentController::class, 'reportEquipment']);
        Route::post("/delay-apply/{equipment_rent_application_id}", [EquipmentController::class, 'delayApply']);
    });
    Route::prefix("activity")->group(function () {
        Route::get("/enroll/{activity_id}", [ActivityController::class, 'enrollActivity']);
        Route::get("/list/{type}", [ActivityController::class, 'listActivityByType']);
        Route::get("/list/application/{type}", [ActivityController::class, 'listActivityApplicationByType']);
    });
    Route::prefix('checkin')->group(function () {
        Route::get("/list/{status}", [CheckInController::class, 'listMyCheckIns']);
        Route::post("/now/{id}", [CheckInController::class, 'checkIn']);
    });
});
Route::middleware("admin")->group(function () {
    Route::get('/files/admin/images/{type}/{filename}', [FileController::class, 'admin']);
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
    Route::prefix("activity")->group(function () {
        Route::prefix("enrollment")->group(function () {
            Route::get("/list", [ActivityController::class, 'listEnrollments']);
            Route::get("/list/{type}", [ActivityController::class, 'listActivityApplicationByType']);
            Route::get("/agree/{enrollment_id}", [ActivityController::class, 'agreeEnrollment']);
            Route::get("/reject/{enrollment_id}", [ActivityController::class, 'rejectEnrollment']);
        });
        Route::post("/search/users/{activity_id}", [ActivityController::class, 'searchUserNotInActivity']);
        Route::delete("/remove/{activity_id}/{user_id}", [ActivityController::class, 'removeUser']);
    });
    Route::apiResource("activity", ActivityController::class);
    Route::prefix('checkin')->group(function () {
        Route::get("/revoke/{id}", [CheckInController::class, 'revokeCheckIn']);
        Route::post("/user/{check_in_id}", [CheckInController::class, 'addUser']);
        Route::delete("/user/{id}", [CheckInController::class, 'removeUser']);
        Route::post("/user/search/{id}", [CheckInController::class, 'searchUserNotInCheckIn']);
    });
    Route::apiResource("checkin", CheckInController::class);
    Route::prefix('/security-history')->group(function () {
        Route::get("/", [ApiHistoryController::class, 'list']);
    });
});
