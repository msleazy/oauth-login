<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
     ->name('socialite.redirect');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
     ->name('socialite.callback');

Route::get('/dashboard', function () {
    return 'Bienvenido, ' . auth()->user()->name;
})->middleware('auth');