# Flows

End-to-end behaviour of the package. Each flow is covered by a feature test in
`tests/Feature` (Testbench, Postgres).

## Registration

`POST /{prefix}/register` `{name, email, password}`

1. The request is validated (`RegisterData`).
2. `RegisterHandler` runs inside the command bus transaction:
   - resolves the current realm from `RealmContext` (null = global pool),
   - rejects a duplicate `(realm, email)` with **422** (`EmailAlreadyTakenException`),
   - hashes the password, creates the `User` aggregate, persists it,
   - the repository publishes `UserRegistered`.
3. Responds `201 { id }`.

## Login

`POST /{prefix}/login` `{email, password}`

1. `LoginHandler` looks the user up by `(current realm, email)`.
2. Wrong email / no password / bad password → **401** (`InvalidCredentialsException`) — the same
   response for all three (no account enumeration).
3. On success, a Sanctum token is issued and returned: `{ token }`.

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

## Session — `/me`

`GET /{prefix}/me` (Bearer token)

Reads the authenticated subject through the `CurrentUser` port and returns
`{ id, name, email, realmId }`. Roles/permissions are **not** here — that is the access package's
concern, surfaced by composing its `Authorizer` into this response.

## Logout

- `POST /{prefix}/logout` — revokes the **current** token → **204**. The token no longer
  authenticates (`/me` → 401).
- `POST /{prefix}/logout-all` — revokes **every** token for the user → **204**. All existing
  sessions are invalidated.

## Multi-tenancy (realm)

The schema is realm-aware (`identity_users.realm_id`, unique `(realm_id, email)` with a partial
index for the global pool). By default `RealmContext` returns `null` → a single global pool. Bind a
host `RealmContext` that resolves a tenant to make registration/login realm-scoped (same email may
then exist as independent accounts per realm).
