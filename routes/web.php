<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Who can do what
|--------------------------------------------------------------------------
| guest          : welcome page, admin login (no sign-up: the system is for the office only)
| admin          : everything: dashboard (browse/search), log FOUND items, log LOST items
|                  for walk-in owners, RESOLVE, release a found item to the person at the
|                  office (claim slip), approve/reject claims, view users, My Reports, Account
| super admin    : everything an admin does + add admin accounts, promote an admin
|                  to super admin (max 2 super admins; demote one first when full),
|                  delete accounts, reset passwords, view the activity log
*/

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // The system is for the Lost and Found office only: everything below needs an Admin / Super Admin account.
    Route::middleware('role:admin')->group(function () {
    Route::get('/dashboard', [ItemController::class, 'dashboard'])->name('dashboard');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    // Lost & found reports
    Route::get('/my/reports', [ItemController::class, 'mine'])->name('items.mine');
    Route::post('/items/{item}/resolve', [ItemController::class, 'resolve'])->name('items.resolve');
    Route::post('/items/{item}/release', [ItemController::class, 'release'])->name('items.release'); // found item -> person at the office
    Route::resource('items', ItemController::class);

    // Claims
    Route::get('/claims/{claim}/photo', [ClaimController::class, 'proof'])->name('claims.proof');
    Route::get('/claims/{claim}/slip', [ClaimController::class, 'slip'])->name('claims.slip');

    // Admin only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('items.index'))->name('dashboard');
        Route::get('/claims', [AdminController::class, 'claims'])->name('claims');
        Route::post('/claims/{claim}/approve', [AdminController::class, 'approve'])->name('claims.approve');
        Route::post('/claims/{claim}/reject', [AdminController::class, 'reject'])->name('claims.reject');
        Route::get('/users', [AdminController::class, 'users'])->name('users');   // admins can view the list

        // Account management: SUPER ADMIN ONLY
        Route::middleware('role:superadmin')->group(function () {
            Route::get('/admins/create', [AdminController::class, 'createAdmin'])->name('admins.create');
            Route::post('/admins', [AdminController::class, 'storeAdmin'])->name('admins.store');
            Route::put('/users/{user}/password', [AdminController::class, 'resetPassword'])->name('users.password');
            Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
            Route::put('/users/{user}/promote', [AdminController::class, 'promote'])->name('users.promote');
            Route::put('/users/{user}/demote', [AdminController::class, 'demote'])->name('users.demote');
            Route::get('/logs', [AdminController::class, 'logs'])->name('logs'); // activity log: super admin only
        });
    });
    });
});
