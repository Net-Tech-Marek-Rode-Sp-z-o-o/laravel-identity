# Usage

## Install

```bash
composer require net-code/laravel-identity
php artisan vendor:publish --tag=identity-config   # optional
php artisan migrate
```

The service provider is auto-discovered. It ships the `identity_users` and (uuid-tokenable)
`personal_access_tokens` migrations, and calls `Sanctum::ignoreMigrations()` so Sanctum's default
bigint-tokenable migration does not clash.

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

## Endpoints

| Method | URI | Auth | Body / result |
|---|---|---|---|
| POST | `/{prefix}/register` | — | `{name,email,password}` → `201 {id}` |
| POST | `/{prefix}/login` | — | `{email,password}` → `{token}` |
| POST | `/{prefix}/logout` | sanctum | `204` (revokes current token) |
| POST | `/{prefix}/logout-all` | sanctum | `204` (revokes all tokens) |
| GET | `/{prefix}/me` | sanctum | `{id,name,email,realmId}` |

Errors are mapped to JSON: invalid credentials → `401`, duplicate email → `422`, user not found →
`404`.

## Ports (hexagonal)

**Inbound** — resolve and use:
- `NetCode\Identity\Application\Port\CurrentUser` — the authenticated subject (`user()`,
  `userOrNull()`, `tokenId()`); bound `scoped`.

**Outbound** — bind a host adapter to override the default:
- `RealmContext` — current realm (default `NullRealmContext` → single global pool). Bind your own to
  resolve a tenant from subdomain/header/path.

**Internal** — swappable adapters (defaults wired): `TokenIssuer`/`TokenRevoker` → Sanctum,
`PasswordHasher` → Laravel Hash, `UserRepository` → Eloquent, `Clock` → `SystemClock`.

## Events

`UserRegistered` and `UserDeleted` are published via the domain event publisher — subscribe for
decoupled follow-ups (welcome mail, provisioning, …).
