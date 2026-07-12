# Flows

End-to-end behaviour of the package. Each flow is covered by a feature test in
`tests/Feature` (Testbench, Postgres). Every non-empty success response is wrapped in a
`{ "data": { … } }` envelope with snake_case fields; `204` responses have no body.

## Registration

`POST /{prefix}/register` `{name, email, password}`

1. The request is validated (`RegisterData`).
2. `RegisterHandler` runs inside the command bus transaction:
   - resolves the current realm from `RealmContext` (null = global pool),
   - rejects a duplicate `(realm, email)` with **422** (`EmailAlreadyTakenException`),
   - hashes the password, creates the `User` aggregate, persists it,
   - the repository publishes `UserRegistered`.
3. Responds `201 { data: { id } }`.

## Login

`POST /{prefix}/login` `{email, password}`

1. `LoginHandler` looks the user up by `(current realm, email)`.
2. Wrong email / no password / bad password → **401** (`InvalidCredentialsException`) — the same
   response for all three (no account enumeration).
3. On success, a Sanctum token is issued and returned: `{ data: { token } }`.

## Password reset

- `POST /{prefix}/password/forgot` `{email}` — `RequestPasswordResetHandler` looks the user up by
  `(current realm, email)`. If found, it generates a high-entropy token, stores its **SHA-256 hash**
  with a TTL (`password_reset_ttl`), and hands the **plaintext** token to `PasswordResetNotifier`.
  If not found, it does nothing. Either way the response is **204** — no account enumeration.
- `POST /{prefix}/password/reset` `{token, password}` — `ResetPasswordHandler` looks the token up by
  its SHA-256 hash, `redeem()`s it (rejecting an expired or already-used token with **422**), then
  changes the user's password (emitting `PasswordChanged`) and marks the token used — all in one
  transaction. Responds **204**.

The default `MailPasswordResetNotifier` emails the raw token; a host binds its own notifier to send a
branded email carrying its frontend reset URL.

## Two-factor authentication (TOTP)

Enrolment is a two-step *enable → confirm* so a user cannot lock themselves out with a mistyped
secret. The TOTP secret is stored **encrypted** (`SecretEncrypter`); recovery codes are stored
**SHA-256 hashed** and shown in plaintext only once.

- `POST /{prefix}/2fa/enable` (authenticated) — `EnableTwoFactor` generates a secret and a set of
  recovery codes, stores them as a **pending** enrolment, and returns
  `{data:{secret, otpauth_uri, recovery_codes}}`. Not yet active.
- `POST /{prefix}/2fa/confirm` `{code}` — `ConfirmTwoFactor` verifies a TOTP code against the pending
  secret and activates 2FA (emitting `TwoFactorEnabled`). A wrong code → **422**.
- `POST /{prefix}/2fa/disable` `{code}` — `DisableTwoFactor` verifies a current TOTP code, then clears
  the enrolment (emitting `TwoFactorDisabled`).
- `POST /{prefix}/2fa/recovery-codes` — `RegenerateRecoveryCodes` replaces the whole set and returns
  the new plaintext codes (requires confirmed 2FA).

**Login with 2FA active.** `POST /{prefix}/login` no longer returns a token; it returns
`{data:{two_factor: true, challenge_token}}`. The `challenge_token` is a **stateless, encrypted,
short-lived** token (`ChallengeTokenFactory`, TTL `two_factor.challenge_ttl`) — no server-side
challenge table.

- `POST /{prefix}/2fa/challenge` `{challengeToken, code}` — `VerifyTwoFactorChallenge` decrypts and
  expiry-checks the challenge token (**401** if invalid/expired), then accepts either a valid **TOTP
  code** or a **recovery code** (which is consumed, single-use). On success it issues the real Sanctum
  token: `{data:{token}}`. A wrong TOTP and unknown recovery code → **422**.

## Session — `/me`

`GET /{prefix}/me` (Bearer token)

Reads the authenticated subject through the `CurrentUser` port and returns
`{ data: { id, name, email, realm_id } }`. Roles/permissions are **not** here — that is the access package's
concern, surfaced by composing its `Authorizer` into this response.

## Logout

- `POST /{prefix}/logout` — revokes the **current** token → **204**. The token no longer
  authenticates (`/me` → 401).
- `POST /{prefix}/logout-all` — revokes **every** token for the user → **204**. All existing
  sessions are invalidated.

## Invitations

An authenticated user invites an email; the invitee accepts with a token, which creates their account.

- `POST /{prefix}/invitations` `{email, metadata?}` (authenticated) — `InviteUser` rejects an email
  that already belongs to a user (**422**), then issues an `Invitation`: a high-entropy token stored
  **SHA-256 hashed** with a TTL (`invitation_ttl`) plus a host-interpreted `metadata` map, and hands
  the plaintext token to `InvitationNotifier`. Returns `{data:{id, email, expires_at}}`.
- `POST /{prefix}/invitations/accept` `{token, name, password}` (public) — `AcceptInvitation` looks
  the invitation up by token hash, `accept()`s it (an expired, already-accepted or revoked one → **422**),
  creates the `User` in the invitation's realm with the given name/password, marks the invitation
  accepted (emitting `InvitationAccepted`), and runs `InvitationAcceptanceHook` with a typed
  `AcceptedInvitation { userId, email, realmId, metadata }` — all in one transaction. Responds
  `201 {data:{id}}`.
- `DELETE /{prefix}/invitations/{invitationId}` (authenticated) — `RevokeInvitation` revokes a pending
  invitation so its token can no longer be accepted → **204**.

Roles/permissions are **not** part of this — the intended role rides in `metadata`, and the host's
acceptance hook (or the access package) turns it into an actual grant.

## Multi-tenancy (realm)

The schema is realm-aware (`identity_users.realm_id`, unique `(realm_id, email)` with a partial
index for the global pool). By default `RealmContext` returns `null` → a single global pool. Bind a
host `RealmContext` that resolves a tenant to make registration/login realm-scoped (same email may
then exist as independent accounts per realm).
