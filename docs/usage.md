# Usage

## Install

```bash
composer require net-code/laravel-identity
php artisan vendor:publish --tag=identity-config   # optional
php artisan migrate
```

The service provider is auto-discovered. It ships the `identity_users` and (uuid-tokenable)
`personal_access_tokens` migrations. Sanctum v4 only *publishes* its default bigint-tokenable
migration (it is not auto-loaded), so the package's uuid-tokenable one does not clash — don't run
`vendor:publish` for Sanctum's migrations.

Configure the Sanctum guard to use this package's model (in the host `config/auth.php`):

```php
'guards' => ['sanctum' => ['driver' => 'sanctum', 'provider' => 'users']],
'providers' => ['users' => ['driver' => 'eloquent', 'model' => NetCode\Identity\Infrastructure\DataAccess\Models\UserModel::class]],
```

## Config (`config/identity.php`)

| Key | Default | Purpose |
|---|---|---|
| `route_prefix` | `auth` | prefix for the shipped routes (`/auth/login`, `/auth/me`, …) |
| `register_enabled` | `true` | whether `POST /register` is exposed |
| `token_name` | `api` | Sanctum token name issued on login |
| `password_reset_ttl` | `60` | reset-token lifetime in minutes |
| `two_factor.issuer` | `null` | TOTP issuer label shown in authenticator apps (falls back to `app.name`) |
| `two_factor.challenge_ttl` | `5` | 2FA challenge-token lifetime in minutes |

## Endpoints

| Method | URI | Auth | Body / result |
|---|---|---|---|
| POST | `/{prefix}/register` | — | `{name,email,password}` → `201 {id}` |
| POST | `/{prefix}/login` | — | `{email,password}` → `{token}`, or `{twoFactorRequired:true,challengeToken}` if 2FA is active |
| POST | `/{prefix}/2fa/challenge` | — | `{challengeToken,code}` → `{token}` (`code` = a TOTP or a recovery code) |
| POST | `/{prefix}/password/forgot` | — | `{email}` → `204` (always; issues a token if the email exists) |
| POST | `/{prefix}/password/reset` | — | `{token,password}` → `204` (`422` if the token is invalid/expired/used) |
| POST | `/{prefix}/logout` | sanctum | `204` (revokes current token) |
| POST | `/{prefix}/logout-all` | sanctum | `204` (revokes all tokens) |
| GET | `/{prefix}/me` | sanctum | `{id,name,email,realmId}` |
| POST | `/{prefix}/2fa/enable` | sanctum | `{secret,otpAuthUri,recoveryCodes}` (starts pending enrolment) |
| POST | `/{prefix}/2fa/confirm` | sanctum | `{code}` → `204` (activates 2FA; `422` on a bad code) |
| POST | `/{prefix}/2fa/disable` | sanctum | `{code}` → `204` (`422` on a bad code) |
| POST | `/{prefix}/2fa/recovery-codes` | sanctum | `{recoveryCodes}` (regenerates, replacing the old set) |

Errors are mapped to JSON: invalid credentials → `401`, invalid/expired 2FA challenge token → `401`,
duplicate email → `422`, invalid reset token → `422`, invalid 2FA code → `422`, 2FA not enrolled →
`422`, user not found → `404`.

## Ports (hexagonal)

**Inbound** — resolve and use:
- `NetCode\Identity\Application\Port\CurrentUser` — the authenticated subject (`user()`,
  `userOrNull()`, `tokenId()`); bound `scoped`.

**Outbound** — bind a host adapter to override the default:
- `RealmContext` — current realm (default `NullRealmContext` → single global pool). Bind your own to
  resolve a tenant from subdomain/header/path.
- `PasswordResetNotifier` — how the reset token reaches the user. The default
  `MailPasswordResetNotifier` sends a plain email with the token; override it to send a branded mail
  containing your frontend reset URL.

**Internal** — swappable adapters (defaults wired): `TokenIssuer`/`TokenRevoker` → Sanctum,
`PasswordHasher` → Laravel Hash, `UserRepository` → Eloquent, `Clock` → `SystemClock`,
`Totp` → `pragmarx/google2fa`, `SecretEncrypter` → Laravel `Crypt`, `RecoveryCodeGenerator` → random,
`ChallengeTokenFactory` → a stateless encrypted token (no challenge table).

## Events

`UserRegistered` and `UserDeleted` are published via the domain event publisher — subscribe for
decoupled follow-ups (welcome mail, provisioning, …).
