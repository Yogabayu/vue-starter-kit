<?php

use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('admin/Dashboard');
    })->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('usersIndex');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
