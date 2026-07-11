# net-code/laravel-identity

Reusable, Sanctum-based **authentication** for Laravel — users, registration, login and
sessions, built with hexagonal ports & adapters. Authorization (roles/permissions) is a
separate concern → `net-code/laravel-access`.

```bash
composer require net-code/laravel-identity
php artisan migrate
```

- **`docs/usage.md`** — install, config, ports, wiring.
- **`docs/flows.md`** — the end-to-end flows.

## v1 scope

Register · login · logout / logout-all · `/me` · the `CurrentUser` port. Realm-aware schema
(single realm by default). 2FA, password reset, social login, invitations are seamed next steps.

## License

MIT
