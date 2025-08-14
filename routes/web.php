<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\UserLeagueController;
use Illuminate\Support\Facades\Auth;
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

Auth::routes(['register' => false, 'reset' => false]);

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('leagues')->controller(LeagueController::class)->group(function() {
    Route::get('/', 'index')->name('leagues.index');
    Route::get('/{league}', 'show')->name('leagues.show');
    Route::get('/{league}/transactions', 'getTransactions')->name('leagues.transactions');
    
    // Admin-only routes
    Route::middleware('admin')->group(function() {
        Route::get('/create', 'create')->name('leagues.create');
        Route::post('/', 'store')->name('leagues.store');
        Route::put('/{league}', 'update')->name('leagues.update');
    });
});

// User League Management Routes (Admin only)
Route::prefix('user-leagues')->controller(UserLeagueController::class)->group(function() {
    // Admin-only routes
    Route::middleware('admin')->group(function() {
        Route::get('/manage', 'manage')->name('user-leagues.manage');
        Route::post('/assign', 'assign')->name('user-leagues.assign');
        Route::delete('/remove', 'remove')->name('user-leagues.remove');
        Route::put('/toggle-access', 'toggleAccess')->name('user-leagues.toggle-access');
        
        // AJAX routes
        Route::get('/league/{league}/users', 'getLeagueUsers')->name('user-leagues.league-users');
        Route::get('/user/{user}/leagues', 'getUserLeagues')->name('user-leagues.user-leagues');
    });
});
