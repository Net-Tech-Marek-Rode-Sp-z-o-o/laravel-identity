<?php

declare(strict_types=1);

namespace NetCode\Identity\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use NetCode\Identity\Application\Commands\EnableTwoFactor\EnableTwoFactorHandler;
use NetCode\Identity\Application\Commands\InviteUser\InviteUserHandler;
use NetCode\Identity\Application\Commands\RequestPasswordReset\RequestPasswordResetHandler;
use NetCode\Identity\Application\EmailVerificationIssuer;
use NetCode\Identity\Application\Ports\AccountDeletionHook;
use NetCode\Identity\Application\Ports\ChallengeTokenFactory;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Application\Ports\EmailVerificationNotifier;
use NetCode\Identity\Application\Ports\InvitationAcceptanceHook;
use NetCode\Identity\Application\Ports\InvitationAccess;
use NetCode\Identity\Application\Ports\InvitationMetadataFactory;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Application\Ports\PasswordHasher;
use NetCode\Identity\Application\Ports\PasswordResetNotifier;
use NetCode\Identity\Application\Ports\PostRegistrationHook;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\RecoveryCodeGenerator;
use NetCode\Identity\Application\Ports\RegistrationPayloadFactory;
use NetCode\Identity\Application\Ports\SecretEncrypter;
use NetCode\Identity\Application\Ports\SocialIdentityProvider;
use NetCode\Identity\Application\Ports\TokenGenerator;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Application\Ports\TokenIssuer;
use NetCode\Identity\Application\Ports\TokenRevoker;
use NetCode\Identity\Application\Ports\Totp;
use NetCode\Identity\Domain\Contracts\EmailVerificationTokenRepository;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Contracts\LinkedAccountRepository;
use NetCode\Identity\Domain\Contracts\PasswordResetTokenRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\Exceptions\InvalidChallengeTokenException;
use NetCode\Identity\Domain\Exceptions\InvalidCredentialsException;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\Exceptions\InvalidResetTokenException;
use NetCode\Identity\Domain\Exceptions\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exceptions\InvalidVerificationTokenException;
use NetCode\Identity\Domain\Exceptions\InvitationNotFoundException;
use NetCode\Identity\Domain\Exceptions\PasswordConfirmationFailedException;
use NetCode\Identity\Domain\Exceptions\SocialAccountAlreadyLinkedException;
use NetCode\Identity\Domain\Exceptions\SocialEmailNotVerifiedException;
use NetCode\Identity\Domain\Exceptions\TwoFactorNotEnrolledException;
use NetCode\Identity\Domain\Exceptions\UserNotFoundException;
use NetCode\Identity\Infrastructure\Auth\SanctumCurrentUser;
use NetCode\Identity\Infrastructure\Challenge\SignedChallengeTokenFactory;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentEmailVerificationTokenRepository;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentInvitationRepository;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentLinkedAccountRepository;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentPasswordResetTokenRepository;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentUserRepository;
use NetCode\Identity\Infrastructure\Deletion\NullAccountDeletionHook;
use NetCode\Identity\Infrastructure\Invitation\EmptyInvitationMetadataFactory;
use NetCode\Identity\Infrastructure\Invitation\InviterOnlyInvitationAccess;
use NetCode\Identity\Infrastructure\Invitation\NullInvitationAcceptanceHook;
use NetCode\Identity\Infrastructure\Mail\MailEmailVerificationNotifier;
use NetCode\Identity\Infrastructure\Mail\MailInvitationNotifier;
use NetCode\Identity\Infrastructure\Mail\MailPasswordResetNotifier;
use NetCode\Identity\Infrastructure\Realm\NullRealmContext;
use NetCode\Identity\Infrastructure\Registration\DefaultRegistrationPayloadFactory;
use NetCode\Identity\Infrastructure\Registration\NullPostRegistrationHook;
use NetCode\Identity\Infrastructure\Sanctum\SanctumTokenIssuer;
use NetCode\Identity\Infrastructure\Sanctum\SanctumTokenRevoker;
use NetCode\Identity\Infrastructure\Security\HashPasswordHasher;
use NetCode\Identity\Infrastructure\Security\LaravelSecretEncrypter;
use NetCode\Identity\Infrastructure\Security\PragmaRxTotp;
use NetCode\Identity\Infrastructure\Security\RandomRecoveryCodeGenerator;
use NetCode\Identity\Infrastructure\Security\RandomTokenGenerator;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;
use NetCode\Identity\Infrastructure\Social\SocialiteIdentityProvider;
use NetCode\Identity\Presentation\Http\Middleware\EnsureEmailIsVerified;
use NetCode\Kit\Clock;
use NetCode\Kit\SystemClock;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/identity.php', 'identity');

        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(RealmContext::class, NullRealmContext::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(PasswordHasher::class, HashPasswordHasher::class);
        $this->app->bind(TokenRevoker::class, SanctumTokenRevoker::class);
        $this->app->bind(TokenGenerator::class, RandomTokenGenerator::class);
        $this->app->bind(TokenHasher::class, Sha256TokenHasher::class);
        $this->app->bind(PasswordResetNotifier::class, MailPasswordResetNotifier::class);
        $this->app->bind(PasswordResetTokenRepository::class, EloquentPasswordResetTokenRepository::class);
        $this->app->bind(EmailVerificationNotifier::class, MailEmailVerificationNotifier::class);
        $this->app->bind(EmailVerificationTokenRepository::class, EloquentEmailVerificationTokenRepository::class);
        $this->app->bind(SecretEncrypter::class, LaravelSecretEncrypter::class);
        $this->app->bind(RecoveryCodeGenerator::class, RandomRecoveryCodeGenerator::class);
        $this->app->bind(ChallengeTokenFactory::class, SignedChallengeTokenFactory::class);
        $this->app->bind(InvitationRepository::class, EloquentInvitationRepository::class);
        $this->app->bind(InvitationNotifier::class, MailInvitationNotifier::class);
        $this->app->bind(InvitationAcceptanceHook::class, NullInvitationAcceptanceHook::class);
        $this->app->bind(InvitationAccess::class, InviterOnlyInvitationAccess::class);
        $this->app->bind(InvitationMetadataFactory::class, EmptyInvitationMetadataFactory::class);
        $this->app->bind(LinkedAccountRepository::class, EloquentLinkedAccountRepository::class);
        $this->app->bind(SocialIdentityProvider::class, SocialiteIdentityProvider::class);
        $this->app->bind(PostRegistrationHook::class, NullPostRegistrationHook::class);
        $this->app->bind(RegistrationPayloadFactory::class, DefaultRegistrationPayloadFactory::class);
        $this->app->bind(AccountDeletionHook::class, NullAccountDeletionHook::class);
        $this->app->scoped(CurrentUser::class, SanctumCurrentUser::class);

        $this->app->bind(Totp::class, fn (): Totp => new PragmaRxTotp(new Google2FA));

        $this->app->bind(TokenIssuer::class, fn (): TokenIssuer => new SanctumTokenIssuer(
            tokenName: (string) config('identity.token_name'),
        ));

        $this->app->when(RequestPasswordResetHandler::class)
            ->needs('$ttlMinutes')
            ->give(fn (): int => (int) config('identity.password_reset_ttl'));

        $this->app->when(EmailVerificationIssuer::class)
            ->needs('$ttlMinutes')
            ->give(fn (): int => (int) config('identity.email_verification_ttl'));

        $this->app->when(SignedChallengeTokenFactory::class)
            ->needs('$ttlMinutes')
            ->give(fn (): int => (int) config('identity.two_factor.challenge_ttl'));

        $this->app->when(EnableTwoFactorHandler::class)
            ->needs('$issuer')
            ->give(fn (): string => (string) (config('identity.two_factor.issuer') ?? config('app.name')));

        $this->app->when(InviteUserHandler::class)
            ->needs('$ttlMinutes')
            ->give(fn (): int => (int) config('identity.invitation_ttl'));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->app->make(Router::class)->aliasMiddleware(name: 'identity.verified', class: EnsureEmailIsVerified::class);

        Route::prefix((string) config('identity.route_prefix'))
            ->middleware('api')
            ->group(__DIR__.'/../../routes/api.php');

        $this->registerExceptionRendering();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/identity.php' => $this->app->configPath('identity.php'),
            ], 'identity-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'identity-migrations');
        }
    }

    private function registerExceptionRendering(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(fn (InvalidCredentialsException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED));
        $handler->renderable(fn (InvalidChallengeTokenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED));
        $handler->renderable(fn (EmailAlreadyTakenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (InvalidResetTokenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (InvalidVerificationTokenException $e): JsonResponse => new JsonResponse(data: ['message' => $e->getMessage()], status: Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (PasswordConfirmationFailedException $e): JsonResponse => new JsonResponse(data: ['message' => $e->getMessage()], status: Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (InvalidInvitationException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (InvitationNotFoundException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND));
        $handler->renderable(fn (InvalidTwoFactorCodeException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (TwoFactorNotEnrolledException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (SocialEmailNotVerifiedException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (SocialAccountAlreadyLinkedException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT));
        $handler->renderable(fn (UserNotFoundException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND));
    }
}
