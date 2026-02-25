<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NonWorkingDayController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;


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

Route::prefix('auth')->group(function () {

    // Login with email + PIN
    Route::post('/login/pin', [AuthController::class, 'pinLogin'])
        ->middleware('throttle:5,1');

    // Login (email + password OR PIN depending on your system)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 attempts per minute

    // Forgot password
    Route::post('/forgot-password',
        [ForgotPasswordController::class, 'sendResetLink']
    )->middleware('throttle:3,1');

    // Reset password
    Route::post('/reset-password',
        [ResetPasswordController::class, 'reset']);

    Route::get('/reset-password/{token}', function ($token) {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    })->name('password.reset');

});

Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Current logged-in user
    Route::get('/me', function (Request $request) {
        return $request->user()->load('role', 'department');
    });

    // Change password
    Route::post('/change-password',
        [AuthController::class, 'changePassword']);

    // Change PIN (for attendance system)
    Route::post('/change-pin',
        [AuthController::class, 'changePin']);
});

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE
    |--------------------------------------------------------------------------
    */

    Route::prefix('attendance')->middleware(['auth:sanctum','role:employee,manager,admin'])->group(function () {

        Route::post('/check', 
            [AttendanceController::class, 'check']
        )->middleware(['throttle:attendance','attendance.guard']);

        Route::get('/history',
            [AttendanceController::class, 'history']);
    });

    /*
    |--------------------------------------------------------------------------
    | LEAVES
    |--------------------------------------------------------------------------
    */

    Route::prefix('leaves')->middleware(['auth:sanctum','role:employee,manager,admin'])->group(function () {

        Route::post('/', [LeaveController::class, 'store']);
        Route::get('/', [LeaveController::class, 'index']);

        Route::put('/{leave}',
            [LeaveController::class, 'update']
        )->middleware('role:admin');

        Route::delete('/{leave}',
            [LeaveController::class, 'destroy']
        )->middleware('role:admin');
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {

        Route::get('/', fn(Request $r) =>
            $r->user()->notifications()->latest()->get()
        );

        Route::post('/{id}/read', function ($id, Request $r) {
            $notification = $r->user()
                ->notifications()
                ->findOrFail($id);

            $notification->update(['is_read' => true]);

            return response()->json(['message'=>'Marked as read']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('reports')
        ->middleware(['auth:sanctum','role:admin,manager','department.scope'])
        ->group(function () {

        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/summary', [ReportController::class, 'summary']);

        Route::get('/export/pdf', [ReportController::class, 'exportPdf']);
        Route::get('/export/excel', [ReportController::class, 'exportExcel']);
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum','role:admin'])->group(function () {

        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('leave-types', LeaveTypeController::class);
        Route::apiResource('non-working-days', NonWorkingDayController::class);
        Route::apiResource('users', UserController::class);

        Route::get('/audit-logs', [AuditController::class, 'index']);

        Route::get('/settings', fn() => \App\Models\Setting::all());
        Route::post('/settings', [SettingController::class, 'update']);

        Route::post('/register', [AuthController::class, 'register']);

        Route::post('/users/{user}/resend-credentials',
            [UserController::class, 'resendCredentials']);
    }); 
