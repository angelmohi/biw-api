<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeagueController;
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
    Route::get('/create', 'create')->name('leagues.create');
    Route::post('/', 'store')->name('leagues.store');
    Route::get('/{league}', 'show')->name('leagues.show');
    Route::put('/{league}', 'update')->name('leagues.update');
});
