<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestImageController;
use App\Http\Controllers\StreetController;
use App\Http\Controllers\UserActivityController;
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

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
});

Route::get('/session/clear', [AuthController::class, 'clearSession'])->name('session.clear');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::redirect('/', '/requests');
    Route::get('/session/keep-alive', [RequestController::class, 'keepAlive'])->name('session.keep-alive');
    Route::match(['post', 'put', 'patch'], '/requests/validate/{registryRequest?}', [RequestController::class, 'validateForm'])->name('requests.validate');
    Route::get('/requests/monitoring', [RequestController::class, 'monitoring'])->name('requests.monitoring');
    Route::get('/requests/export', [RequestController::class, 'export'])->name('requests.export');
    Route::resource('requests', RequestController::class)->parameters(['requests' => 'registryRequest']);
    Route::post('/api/check-cadastre-restriction', [RequestController::class, 'checkCadastreRestriction'])->name('cadastre.check');
    Route::delete('/request-images/{image}', [RequestImageController::class, 'destroy'])->name('request-images.destroy');
    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::get('/addresses/{district}', [AddressController::class, 'show'])->name('addresses.show');
    Route::post('/streets/store', [StreetController::class, 'store'])->name('streets.store');
    Route::get('/users/online', [UserActivityController::class, 'online'])->name('users.online');
});
