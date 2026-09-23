<?php

use App\Http\Controllers\CarteraController;
use App\Http\Controllers\DeudacarteraController;
use App\Http\Controllers\HistorialcarteraController;
use App\Http\Controllers\HomeController;
use App\Livewire\CarteraManagement;
use App\Livewire\Lotes\ConstructorLote as LotesConstructorLote;
use App\Livewire\Lotes\Index;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group([
    'middleware' => 'auth'
], function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::view('/usuarios', 'users.index')->name('users.index');
    Route::view('/roles-permisos', 'roles.index')->name('roles.index');
    Route::view('/empresas', 'companies.index')->name('companies.index');
    Route::view('/carteras', 'carteras.index')->name('carteras');
    Route::view('/deudores', 'deudores.index')->name('deudores.index');
    Route::view('/analisis', 'analisis.index')->name('analisis.index');


    // COMPONENTES LIVEWIRE   
    Route::middleware(['role:JefeVentas|Admin'])->group(function () {
        Route::get('/carteras/{cartera}/constructor-lotes', LotesConstructorLote::class)
            ->name('carteras.constructor-lotes');
    });

    Route::get('/lotes', Index::class)->name('lotes.index');


    // Route::resource('carteras', CarteraController::class)->names('carteras');
    Route::resource('historialcarteras', HistorialcarteraController::class)->names('historialcarteras');
    Route::resource('deudacarteras', DeudacarteraController::class)->names('deudacarteras');
});

use App\Livewire\DeudasCarteraIndex;

Route::middleware(['auth'])->group(function () {
    // ... otras rutas
    Route::get('/carteras/{cartera}/deudas', DeudasCarteraIndex::class)
        ->name('carteras.deudas');
});
