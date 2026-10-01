<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NetCode\Identity\Presentation\Http\Controllers\AcceptInvitationController;
use NetCode\Identity\Presentation\Http\Controllers\ConfirmTwoFactorController;
use NetCode\Identity\Presentation\Http\Controllers\DeleteAccountController;
use NetCode\Identity\Presentation\Http\Controllers\DisableTwoFactorController;
use NetCode\Identity\Presentation\Http\Controllers\EnableTwoFactorController;
use NetCode\Identity\Presentation\Http\Controllers\InviteUserController;
use NetCode\Identity\Presentation\Http\Controllers\LinkSocialAccountController;
use NetCode\Identity\Presentation\Http\Controllers\LoginController;
use NetCode\Identity\Presentation\Http\Controllers\LogoutAllController;
use NetCode\Identity\Presentation\Http\Controllers\LogoutController;
use NetCode\Identity\Presentation\Http\Controllers\MeController;
use NetCode\Identity\Presentation\Http\Controllers\RegenerateRecoveryCodesController;
use NetCode\Identity\Presentation\Http\Controllers\RegisterController;
use NetCode\Identity\Presentation\Http\Controllers\RequestPasswordResetController;
use NetCode\Identity\Presentation\Http\Controllers\ResendEmailVerificationController;
use NetCode\Identity\Presentation\Http\Controllers\ResetPasswordController;
use NetCode\Identity\Presentation\Http\Controllers\RevokeInvitationController;
use NetCode\Identity\Presentation\Http\Controllers\SocialLoginController;
use NetCode\Identity\Presentation\Http\Controllers\TwoFactorChallengeController;
use NetCode\Identity\Presentation\Http\Controllers\VerifyEmailController;

Route::post('login', LoginController::class)->middleware('throttle:identity-login');
Route::post('2fa/challenge', TwoFactorChallengeController::class)->middleware('throttle:identity-two-factor');
Route::post('password/forgot', RequestPasswordResetController::class)->middleware('throttle:identity-password-request');
Route::post('password/reset', ResetPasswordController::class)->middleware('throttle:identity-password');
Route::post('invitations/accept', AcceptInvitationController::class)->middleware('throttle:identity-tokens');
Route::post('email/verify', VerifyEmailController::class)->middleware('throttle:identity-tokens');
Route::post('{provider}/login', SocialLoginController::class)->middleware('throttle:identity-login');

if (config('identity.register_enabled') === true) {
    Route::post('register', RegisterController::class)->middleware('throttle:identity-register');
}

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', LogoutController::class);
    Route::post('logout-all', LogoutAllController::class);
    Route::get('me', MeController::class);
    Route::delete('me', DeleteAccountController::class);
    Route::post('email/resend', ResendEmailVerificationController::class)->middleware('throttle:identity-mail');

    Route::post('2fa/enable', EnableTwoFactorController::class);
    Route::post('2fa/confirm', ConfirmTwoFactorController::class);
    Route::post('2fa/disable', DisableTwoFactorController::class);
    Route::post('2fa/recovery-codes', RegenerateRecoveryCodesController::class);

    Route::post('invitations', InviteUserController::class)->middleware(['identity.verified', 'throttle:identity-mail']);
    Route::delete('invitations/{invitationId}', RevokeInvitationController::class)->whereUuid('invitationId');

    Route::post('{provider}/link', LinkSocialAccountController::class);
});
