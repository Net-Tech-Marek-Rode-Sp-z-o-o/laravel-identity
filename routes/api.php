<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NetCode\Identity\Presentation\Http\Controllers\AcceptInvitationController;
use NetCode\Identity\Presentation\Http\Controllers\ConfirmTwoFactorController;
use NetCode\Identity\Presentation\Http\Controllers\DisableTwoFactorController;
use NetCode\Identity\Presentation\Http\Controllers\EnableTwoFactorController;
use NetCode\Identity\Presentation\Http\Controllers\InviteUserController;
use NetCode\Identity\Presentation\Http\Controllers\LoginController;
use NetCode\Identity\Presentation\Http\Controllers\LogoutAllController;
use NetCode\Identity\Presentation\Http\Controllers\LogoutController;
use NetCode\Identity\Presentation\Http\Controllers\MeController;
use NetCode\Identity\Presentation\Http\Controllers\RegenerateRecoveryCodesController;
use NetCode\Identity\Presentation\Http\Controllers\RegisterController;
use NetCode\Identity\Presentation\Http\Controllers\RequestPasswordResetController;
use NetCode\Identity\Presentation\Http\Controllers\ResetPasswordController;
use NetCode\Identity\Presentation\Http\Controllers\RevokeInvitationController;
use NetCode\Identity\Presentation\Http\Controllers\TwoFactorChallengeController;

Route::post('login', LoginController::class);
Route::post('2fa/challenge', TwoFactorChallengeController::class);
Route::post('password/forgot', RequestPasswordResetController::class);
Route::post('password/reset', ResetPasswordController::class);
Route::post('invitations/accept', AcceptInvitationController::class);

if (config('identity.register_enabled') === true) {
    Route::post('register', RegisterController::class);
}

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', LogoutController::class);
    Route::post('logout-all', LogoutAllController::class);
    Route::get('me', MeController::class);

    Route::post('2fa/enable', EnableTwoFactorController::class);
    Route::post('2fa/confirm', ConfirmTwoFactorController::class);
    Route::post('2fa/disable', DisableTwoFactorController::class);
    Route::post('2fa/recovery-codes', RegenerateRecoveryCodesController::class);

    Route::post('invitations', InviteUserController::class);
    Route::delete('invitations/{invitationId}', RevokeInvitationController::class);
});
