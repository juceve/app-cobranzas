<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::view('/usuarios', 'users.index')
    ->middleware('auth')
    ->name('users.index');

Route::view('/roles-permisos', 'roles.index')
    ->middleware('auth')
    ->name('roles.index');

Route::view('/empresas', 'companies.index')
    ->middleware('auth')
    ->name('companies.index');
