<?php

declare(strict_types=1);

namespace NetCode\Identity\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use NetCode\Identity\Application\Command\EnableTwoFactor\EnableTwoFactorHandler;
use NetCode\Identity\Application\Command\RequestPasswordReset\RequestPasswordResetHandler;
use NetCode\Identity\Application\Port\ChallengeTokenFactory;
use NetCode\Identity\Application\Port\CurrentUser;
use NetCode\Identity\Application\Port\PasswordHasher;
use NetCode\Identity\Application\Port\PasswordResetNotifier;
use NetCode\Identity\Application\Port\RealmContext;
use NetCode\Identity\Application\Port\RecoveryCodeGenerator;
use NetCode\Identity\Application\Port\SecretEncrypter;
use NetCode\Identity\Application\Port\TokenGenerator;
use NetCode\Identity\Application\Port\TokenIssuer;
use NetCode\Identity\Application\Port\TokenRevoker;
use NetCode\Identity\Application\Port\Totp;
use NetCode\Identity\Domain\Contract\PasswordResetTokenRepository;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\EmailAlreadyTakenException;
use NetCode\Identity\Domain\Exception\InvalidChallengeTokenException;
use NetCode\Identity\Domain\Exception\InvalidCredentialsException;
use NetCode\Identity\Domain\Exception\InvalidResetTokenException;
use NetCode\Identity\Domain\Exception\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exception\TwoFactorNotEnrolledException;
use NetCode\Identity\Domain\Exception\UserNotFoundException;
use NetCode\Identity\Infrastructure\Auth\SanctumCurrentUser;
use NetCode\Identity\Infrastructure\Challenge\SignedChallengeTokenFactory;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentPasswordResetTokenRepository;
use NetCode\Identity\Infrastructure\DataAccess\Repositories\EloquentUserRepository;
use NetCode\Identity\Infrastructure\Mail\MailPasswordResetNotifier;
use NetCode\Identity\Infrastructure\Realm\NullRealmContext;
use NetCode\Identity\Infrastructure\Sanctum\SanctumTokenIssuer;
use NetCode\Identity\Infrastructure\Sanctum\SanctumTokenRevoker;
use NetCode\Identity\Infrastructure\Security\HashPasswordHasher;
use NetCode\Identity\Infrastructure\Security\LaravelSecretEncrypter;
use NetCode\Identity\Infrastructure\Security\PragmaRxTotp;
use NetCode\Identity\Infrastructure\Security\RandomRecoveryCodeGenerator;
use NetCode\Identity\Infrastructure\Security\RandomTokenGenerator;
use NetCode\Kit\Clock;
use NetCode\Kit\SystemClock;
use PragmaRX\Google2FA\Google2FA;

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
        $this->app->bind(PasswordResetNotifier::class, MailPasswordResetNotifier::class);
        $this->app->bind(PasswordResetTokenRepository::class, EloquentPasswordResetTokenRepository::class);
        $this->app->bind(SecretEncrypter::class, LaravelSecretEncrypter::class);
        $this->app->bind(RecoveryCodeGenerator::class, RandomRecoveryCodeGenerator::class);
        $this->app->bind(ChallengeTokenFactory::class, SignedChallengeTokenFactory::class);
        $this->app->scoped(CurrentUser::class, SanctumCurrentUser::class);

        $this->app->bind(Totp::class, fn (): Totp => new PragmaRxTotp(new Google2FA));

        $this->app->bind(TokenIssuer::class, fn (): TokenIssuer => new SanctumTokenIssuer(
            tokenName: (string) config('identity.token_name'),
        ));

        $this->app->when(RequestPasswordResetHandler::class)
            ->needs('$ttlMinutes')
            ->give(fn (): int => (int) config('identity.password_reset_ttl'));

        $this->app->when(SignedChallengeTokenFactory::class)
            ->needs('$ttlMinutes')
            ->give(fn (): int => (int) config('identity.two_factor.challenge_ttl'));

        $this->app->when(EnableTwoFactorHandler::class)
            ->needs('$issuer')
            ->give(fn (): string => (string) (config('identity.two_factor.issuer') ?? config('app.name')));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

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

        $handler->renderable(fn (InvalidCredentialsException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 401));
        $handler->renderable(fn (InvalidChallengeTokenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 401));
        $handler->renderable(fn (EmailAlreadyTakenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 422));
        $handler->renderable(fn (InvalidResetTokenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 422));
        $handler->renderable(fn (InvalidTwoFactorCodeException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 422));
        $handler->renderable(fn (TwoFactorNotEnrolledException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 422));
        $handler->renderable(fn (UserNotFoundException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 404));
    }
}
