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
| `invitation_ttl` | `4320` | invitation lifetime in minutes (default 3 days) |
| `two_factor.issuer` | `null` | TOTP issuer label shown in authenticator apps (falls back to `app.name`) |
| `two_factor.challenge_ttl` | `5` | 2FA challenge-token lifetime in minutes |

## Endpoints

Request bodies use spatie-Data (validated). **Every non-empty success response is wrapped in a
`{ "data": { … } }` envelope (Laravel API resources) with snake_case fields.** `204` responses have
no body.

| Method | URI | Auth | Body → result |
|---|---|---|---|
| POST | `/{prefix}/register` | — | `{name,email,password}` → `201 {data:{id}}` |
| POST | `/{prefix}/login` | — | `{email,password}` → `{data:{token}}`, or `{data:{two_factor:true,challenge_token}}` if 2FA is active |
| POST | `/{prefix}/2fa/challenge` | — | `{challengeToken,code}` → `{data:{token}}` (`code` = a TOTP or a recovery code) |
| POST | `/{prefix}/password/forgot` | — | `{email}` → `204` (always; issues a token if the email exists) |
| POST | `/{prefix}/password/reset` | — | `{token,password}` → `204` (`422` if the token is invalid/expired/used) |
| POST | `/{prefix}/invitations/accept` | — | `{token,name,password}` → `201 {data:{id}}` (creates the user; `422` if invalid/expired/revoked) |
| POST | `/{prefix}/{provider}/login` | — | `{accessToken}` → `{data:{token}}` (social login/register; `provider` = `google`\|`facebook`, else `404`) |
| POST | `/{prefix}/logout` | sanctum | `204` (revokes current token) |
| POST | `/{prefix}/logout-all` | sanctum | `204` (revokes all tokens) |
| GET | `/{prefix}/me` | sanctum | `{data:{id,name,email,realm_id}}` |
| POST | `/{prefix}/2fa/enable` | sanctum | `{data:{secret,otpauth_uri,recovery_codes}}` (starts pending enrolment) |
| POST | `/{prefix}/2fa/confirm` | sanctum | `{code}` → `204` (activates 2FA; `422` on a bad code) |
| POST | `/{prefix}/2fa/disable` | sanctum | `{code}` → `204` (`422` on a bad code) |
| POST | `/{prefix}/2fa/recovery-codes` | sanctum | `{data:{recovery_codes}}` (regenerates, replacing the old set) |
| POST | `/{prefix}/invitations` | sanctum | `{email,metadata?}` → `201 {data:{id,email,expires_at}}` (`422` if the email is already a user) |
| DELETE | `/{prefix}/invitations/{invitationId}` | sanctum | `204` (revokes a pending invitation) |
| POST | `/{prefix}/{provider}/link` | sanctum | `{accessToken}` → `204` (links a social account to the current user; `409` if already linked elsewhere) |

Errors are mapped to JSON: invalid credentials → `401`, invalid/expired 2FA challenge token → `401`,
duplicate email → `422`, invalid reset token → `422`, invalid/expired/revoked invitation → `422`,
invalid 2FA code → `422`, 2FA not enrolled → `422`, unverified social email on an existing user →
`422`, social account already linked → `409`, user not found → `404`.

## Ports (hexagonal)

**Inbound** — resolve and use:
- `NetCode\Identity\Application\Ports\CurrentUser` — the authenticated subject (`user()`,
  `userOrNull()`, `tokenId()`); bound `scoped`.

**Outbound** — bind a host adapter to override the default:
- `RealmContext` — current realm (default `NullRealmContext` → single global pool). Bind your own to
  resolve a tenant from subdomain/header/path.
- `PasswordResetNotifier` — how the reset token reaches the user. The default
  `MailPasswordResetNotifier` sends a plain email with the token; override it to send a branded mail
  containing your frontend reset URL.
- `InvitationNotifier` — how the invitation token reaches the invitee (default `MailInvitationNotifier`,
  a plain email); override for a branded mail with your frontend accept URL.
- `InvitationAcceptanceHook` — runs after an invitation is accepted (default no-op). Bind your own to
  act on the host-interpreted `metadata` (e.g. assign the invited role/tenant) — it receives a typed
  `AcceptedInvitation { userId, email, realmId, metadata }`.
- `PostRegistrationHook<TPayload>` + `RegistrationPayloadFactory` — run host logic inside the register
  transaction. The factory maps the register request into a host-typed `RegistrationPayload` (defaults
  to `NoRegistrationPayload`); the hook (default no-op) receives the new `RegisteredUser` + that typed
  payload — e.g. provision an organization from extra register fields. Complementary to the
  `UserRegistered` event (hook = finish registration atomically; event = broadcast the fact).

**Internal** — swappable adapters (defaults wired): `TokenIssuer`/`TokenRevoker` → Sanctum,
`PasswordHasher` → Laravel Hash, `UserRepository` → Eloquent, `Clock` → `SystemClock`,
`Totp` → `pragmarx/google2fa`, `SecretEncrypter` → Laravel `Crypt`, `RecoveryCodeGenerator` → random,
`TokenHasher` → SHA-256 (hashes reset/invitation/recovery tokens for at-rest lookup),
`ChallengeTokenFactory` → a stateless encrypted token (no challenge table), `SocialIdentityProvider`
→ `laravel/socialite` (configure each provider's client id/secret in the host's `config/services.php`;
the SPA obtains the provider access token and posts it to `/{provider}/login|link`).

## Events

`UserRegistered`, `UserDeleted`, `PasswordChanged`, `TwoFactorEnabled`/`TwoFactorDisabled` and
`InvitationAccepted` are published via the domain event publisher — subscribe for decoupled
follow-ups (welcome mail, provisioning, …).
