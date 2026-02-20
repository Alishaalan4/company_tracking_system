<?php

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


// - throttle:attendance → Prevents abuse of the attendance check endpoint (rate limiting).
// - attendance.guard → Stops users from checking in/out multiple times in one day (your AttendanceGuardMiddleware).


Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE
    |--------------------------------------------------------------------------
    */

    Route::prefix('attendance')->group(function () {

        Route::post('/check', 
            [AttendanceController::class, 'check']
        )->middleware([
            'role:employee,manager,admin',
            'throttle:attendance',
            'attendance.guard'
        ]);

        Route::get('/history',
            [AttendanceController::class, 'history']
        )->middleware('role:employee,manager,admin');

    });

    /*
    |--------------------------------------------------------------------------
    | LEAVE REQUESTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('leaves')->group(function () {

        Route::post('/',
            [LeaveController::class, 'store']
        )->middleware('role:employee,manager,admin');

        Route::get('/',
            [LeaveController::class, 'index']
        );

        Route::put('/{leave}',
            [LeaveController::class, 'update']
        )->middleware('role:admin');

        Route::delete('/{leave}',
            [LeaveController::class, 'destroy']
        )->middleware('role:admin');
    });

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('reports')
        ->middleware(['role:admin,manager'])
        ->group(function () {

        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/summary', [ReportController::class, 'summary']);

        Route::get('/export/pdf', [ReportController::class, 'exportPdf']);
        Route::get('/export/excel', [ReportController::class, 'exportExcel']);
    });

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENTS (ADMIN)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('leave-types', LeaveTypeController::class);
        Route::apiResource('non-working-days', NonWorkingDayController::class);
        Route::apiResource('users', UserController::class);

        Route::get('/audit-logs', [AuditController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);
    });

});