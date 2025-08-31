<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminRevisionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Models\User;

    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'login']);

    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (Request $request) {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403, '署名が無効です。');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/attendance');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', '認証メールを再送しました。');
    })->middleware(['throttle:6,1'])->name('verification.send');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'startAttendance']);
        Route::patch('/attendance', [AttendanceController::class, 'endAttendance']);
        Route::post('/break', [AttendanceController::class, 'startBreak']);
        Route::patch('/break', [AttendanceController::class, 'endBreak']);
        Route::get('/attendance/list', [AttendanceController::class, 'showList'])->name('attendance.list');
        Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/attendance/detail/{id}', [AttendanceController::class, 'request'])->name('attendance.request');
        Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestList']);
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.list');
        Route::get('/admin/staff/list', [AdminUserController::class, 'listUsers']);
        Route::get('/admin/attendance/staff/{id}', [AdminUserController::class, 'showUserAttendances'])->name('admin.user.attendance');
        Route::post('/admin/export', [AdminUserController::class, 'export'])->name('admin.attendance.export');
        Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminRevisionRequestController::class, 'requestShow'])->name('admin.request.show');
        Route::patch('/stamp_correction_request/approve/{attendance_correct_request}', [AdminRevisionRequestController::class, 'approved'])->name('admin.approved');
    });

    Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.show');
        Route::post('/attendance/{id}', [AdminRevisionRequestController::class, 'request'])->name('admin.request');
        Route::get('/stamp_correction_request/list', [AdminRevisionRequestController::class, 'requestList'])->name('admin.request.list');
    });