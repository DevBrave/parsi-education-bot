# Website login regression tests

The website uses `GET /login` and the app-owned `Auth\LoginController` to
render `resources/js/pages/auth/Login.vue`. The form submits to Fortify's
`POST /login` route. Successful authentication returns to the previously
requested page, or `/` when there is no intended destination.

All `/admin` routes, including the legacy admin login, require an
authenticated user with one of the existing staff roles. Students and
unrecognized roles receive HTTP 403. Existing permissions within the panel
still apply, so a viewer cannot manage users.

## Run the regression tests

Use PHP 8.4.1 or newer to satisfy the locked Symfony dependencies. Install
the project's Composer and npm dependencies, enable PHP's `pdo_sqlite`
extension, then run:

```sh
php artisan wayfinder:generate --with-form
composer test:login
npm run test:login
```

On Windows, when SQLite is installed but not enabled in `php.ini`, run the
backend tests with:

```powershell
php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --group=website-login
npm.cmd run test:login
```

The backend tests use `RefreshDatabase` and the in-memory SQLite database
configured in `phpunit.xml`. They do not require a running database server
or modify the application's regular database.

## Coverage

The PHPUnit `website-login` group covers:

- The custom Inertia login page and session status messages.
- Real password authentication for students and every supported staff role.
- Invalid passwords, unknown accounts, and missing credentials.
- Passwords being excluded from flashed validation input.
- Session ID rotation, remember-me cookies, rate limiting, and logout.
- Default and intended redirects, including students attempting admin URLs.
- Guest and student rejection across read and write admin endpoints.
- Access for staff, rejection of unknown roles, and viewer permission limits.
- Safe authentication props on the homepage and cached dashboard behavior.

The Node tests compile and render the actual Vue components with Vue's
server renderer and Inertia. They check that guests see **Log in**, students
see **My account**, staff see the admin links, and the login form submits
the expected fields to `/login`. They also verify that status messages are
escaped. These tests run without a browser or additional test packages.

The `Website login regressions` GitHub Actions job runs the backend and Vue
tests, a production frontend build, and TypeScript checks on pull requests
and pushes to `main`.

## Broader checks

The repository's existing `ci` job remains enabled. Its PHP 8.3 setup is
incompatible with the locked dependencies; the login job uses PHP 8.4.
The full PHP suite and
PHPStan have failures outside this feature, including missing database
setup in older tests and two-factor fields absent from the user schema.
Passing the login regression group does not claim that the full repository
suite passes.
