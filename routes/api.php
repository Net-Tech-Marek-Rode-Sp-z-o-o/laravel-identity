<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NetCode\Identity\Presentation\Http\Controllers\LoginController;
use NetCode\Identity\Presentation\Http\Controllers\LogoutAllController;
use NetCode\Identity\Presentation\Http\Controllers\LogoutController;
use NetCode\Identity\Presentation\Http\Controllers\MeController;
use NetCode\Identity\Presentation\Http\Controllers\RegisterController;

Route::post('login', LoginController::class);

if (config('identity.register_enabled') === true) {
    Route::post('register', RegisterController::class);
}

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', LogoutController::class);
    Route::post('logout-all', LogoutAllController::class);
    Route::get('me', MeController::class);
});
