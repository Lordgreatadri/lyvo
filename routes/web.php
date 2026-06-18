<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Phase 1 — Architectural prototype. These routes wire the full LYVO user
| journey to demo to the client. Screens use placeholder data via
| App\Support\DemoData and resolve records by UUID (never auto-increment PK)
| to mirror the security model that will be enforced once the DB lands.
|
*/

// ---------- Public ----------
Route::get('/', [HomeController::class, 'index'])->name('home');

// Verified operator directory + profiles (resolved by uuid)
Route::get('/operators', [DirectoryController::class, 'index'])->name('directory.index');
Route::get('/operators/{operator}', [DirectoryController::class, 'show'])->name('directory.show');

// Guest access — browse without an account
Route::get('/guest', function () {
    session(['lyvo_guest' => true]);

    return redirect()->route('directory.index');
})->name('guest.enter');

// Operator onboarding wizard
Route::get('/become-an-operator', [OperatorDashboardController::class, 'register'])->name('register.operator');

// ---------- Escrow (demo: open for walkthrough) ----------
Route::get('/escrow', [EscrowController::class, 'index'])->name('escrow.index');
Route::get('/escrow/{transaction}', [EscrowController::class, 'show'])->name('escrow.show');

// ---------- Customer workspace ----------
Route::get('/customer', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');

// ---------- Operator workspace ----------
Route::get('/operator', [OperatorDashboardController::class, 'index'])->name('operator.dashboard');
Route::get('/operator/verification', [OperatorDashboardController::class, 'verification'])->name('operator.verification');

// ---------- Admin workspace ----------
Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/verification', [AdminDashboardController::class, 'verification'])->name('admin.verification');

// ---------- Authenticated profile (Breeze) ----------
Route::get('/dashboard', [CustomerDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
