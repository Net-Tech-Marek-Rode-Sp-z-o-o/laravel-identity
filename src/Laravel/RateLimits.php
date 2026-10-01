<?php

declare(strict_types=1);

namespace NetCode\Identity\Laravel;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use NetCode\Identity\Application\Ports\ChallengeTokenFactory;

final class RateLimits
{
    public static function register(): void
    {
        self::registerSignIn();
        self::registerPasswords();

        RateLimiter::for('identity-register', static fn (Request $request): Limit => self::perMinute('register_per_minute')->by('ip:'.$request->ip()));
        RateLimiter::for('identity-tokens', static fn (Request $request): Limit => self::perMinute('tokens_per_minute')->by('ip:'.$request->ip()));
        RateLimiter::for('identity-mail', static fn (Request $request): Limit => Limit::perHour(self::number('mail_per_hour'))
            ->by('user:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    private static function registerSignIn(): void
    {
        RateLimiter::for('identity-login', static fn (Request $request): array => [
            self::perMinute('login_per_minute')->by('email:'.self::email($request).'|'.$request->ip()),
            self::perMinute('login_per_minute_per_ip')->by('ip:'.$request->ip()),
        ]);
        RateLimiter::for('identity-two-factor', static fn (Request $request): array => [
            self::perMinute('two_factor_per_minute')->by('ip:'.$request->ip()),
            self::perMinute('two_factor_per_minute')->by('user:'.self::challengedUser($request)),
        ]);
    }

    private static function registerPasswords(): void
    {
        RateLimiter::for('identity-password-request', static fn (Request $request): array => [
            Limit::perHour(self::number('password_requests_per_hour_per_email'))->by('email:'.self::email($request)),
            Limit::perHour(self::number('password_requests_per_hour_per_ip'))->by('ip:'.$request->ip()),
        ]);
        RateLimiter::for('identity-password', static fn (Request $request): Limit => self::perMinute('password_per_minute')->by('ip:'.$request->ip()));
    }

    private static function perMinute(string $key): Limit
    {
        return Limit::perMinute(self::number($key));
    }

    private static function number(string $key): int
    {
        return (int) config('identity.throttle.'.$key);
    }

    private static function email(Request $request): string
    {
        $email = $request->input('email');

        return is_string($email) ? mb_strtolower(trim($email)) : '';
    }

    private static function challengedUser(Request $request): string
    {
        $token = $request->input('challengeToken');
        $userId = is_string($token) ? app(ChallengeTokenFactory::class)->verify($token) : null;

        return $userId?->value() ?? 'unknown|'.$request->ip();
    }
}
