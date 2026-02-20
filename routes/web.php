<?php

use App\Http\Controllers\Admin\BalanceController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::post('/webhook/pawapay', WebhookController::class)->name('webhook.pawapay');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'admin'])
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/balances', BalanceController::class)->name('balances.index');

        Route::get('/payouts/create', [AdminPayoutController::class, 'create'])->name('payouts.create');
        Route::post('/payouts', [AdminPayoutController::class, 'store'])->name('payouts.store');
        Route::get('/payouts/history', [AdminPayoutController::class, 'history'])->name('payouts.history');
        Route::get('/payouts/{payout}/poll', [AdminPayoutController::class, 'pollStatus'])->name('payouts.poll');
    });

require __DIR__ . '/auth.php';
