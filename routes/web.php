<?php

use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\DestinationController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\DestinationImageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $totalUsers = \App\Models\User::count();
        $newUsersWeek = \App\Models\User::where('created_at', '>=', now()->subDays(7))->count();
        $verifiedUsers = \App\Models\User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = $totalUsers - $verifiedUsers;

        $recentUsers = \App\Models\User::latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at']);

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'newUsersWeek' => $newUsersWeek,
                'verifiedUsers' => $verifiedUsers,
                'unverifiedUsers' => $unverifiedUsers,
            ],
            'recentUsers' => $recentUsers,
        ]);
    })->name('dashboard');

    // user management (super_admin only)
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('usersIndex');
        Route::post('/users', [UserController::class, 'create'])->name('users.create');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/users/roles', [UserController::class, 'getRoles'])->name('users.getRoles');
    });

    // Protected CRUD for content (super_admin or admin_destinasi)
    Route::middleware(['role:super_admin,admin_destinasi'])->group(function () {
        Route::resource('destinations', DestinationController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('destinations/{destination}/images', [DestinationImageController::class, 'store'])->name('destinations.images.store');
        Route::delete('destinations/{destination}/images/{image}', [DestinationImageController::class, 'destroy'])->name('destinations.images.destroy');
        Route::resource('categories', CategoryController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('events', EventController::class)->only(['store', 'update', 'destroy']);
        Route::resource('banners', BannerController::class)->only(['store', 'update', 'destroy']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

// Public read-only routes
// Route::resource('categories', CategoryController::class)->only(['index', 'show']);
// Route::resource('events', EventController::class)->only(['index', 'show']);
// Route::resource('banners', BannerController::class)->only(['index', 'show']);
// Route::get('comments', [CommentController::class, 'index'])->name('comments.index');
// Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
