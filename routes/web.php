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
| guest          : welcome page, login, register (students and faculty)
| any logged in  : dashboard (browse/search), full details, report lost items,
|                  edit/delete OWN open reports, My Reports, Account, change password
| student/faculty: claim a found item (Claim Verification), My Claims,
|                  cancel own pending claim
| admin          : log surrendered (FOUND) items, log LOST items for walk-in owners
|                  without an account, approve/reject claims, edit/resolve/
|                  delete any report, view users
| super admin    : everything an admin does + add admin accounts, promote an admin
|                  to super admin (max 2 super admins; demote one first when full),
|                  delete accounts, reset passwords, view the activity log
*/

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [ItemController::class, 'dashboard'])->name('dashboard');

    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    // Lost & found reports
    Route::get('/my/reports', [ItemController::class, 'mine'])->name('items.mine');
    Route::post('/items/{item}/resolve', [ItemController::class, 'resolve'])->name('items.resolve');
    Route::resource('items', ItemController::class);

    // Claims
    Route::get('/claims/{claim}/photo', [ClaimController::class, 'proof'])->name('claims.proof');
    Route::get('/claims/{claim}/slip', [ClaimController::class, 'slip'])->name('claims.slip');

    Route::middleware('role:member')->group(function () {
        Route::get('/items/{item}/claim', [ClaimController::class, 'create'])->name('claims.create');
        Route::post('/items/{item}/claim', [ClaimController::class, 'store'])->name('claims.store');
        Route::get('/my/claims', [ClaimController::class, 'mine'])->name('claims.mine');
        Route::delete('/claims/{claim}', [ClaimController::class, 'destroy'])->name('claims.destroy');
    });

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
