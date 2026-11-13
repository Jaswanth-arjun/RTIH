# Testing — TaskFlow

This document records the automated suite, the manual acceptance checklist, and the defects found and
fixed while building the project.

---

## 1. Automated integration suite

**File:** `tests/run_tests.php`
**Command:** `php tests/run_tests.php`
**Latest result:** **29 passed, 0 failed**

The suite is an end-to-end test of the real application:

- Creates and migrates a dedicated `task_tracker_test` database from `database.sql`.
- Starts PHP's built-in server on `127.0.0.1:8099`.
- Uses cURL with a cookie jar per simulated browser, so sessions and CSRF tokens behave like a real client.
- Follows no redirects automatically; it asserts status codes, headers and response bodies explicitly.
- Drops the test database and stops the server in a shutdown handler, including on failure.

### Test cases

| # | Test | What it proves |
|---:|---|---|
| 1 | Guest is redirected from the dashboard to the login page | Authentication gate on `/tasks` |
| 2 | Security headers and hardened session cookie are sent | CSP, `X-Frame-Options`, `nosniff`, HttpOnly/SameSite cookie |
| 3 | Wrong password is rejected without revealing whether the account exists | Generic login error, no user enumeration |
| 4 | Login rejects an empty email address with a friendly validation error | Server-side login validation |
| 5 | A valid member login opens the dashboard with seeded tasks and stats | Happy-path login and dashboard rendering |
| 6 | State-changing requests without a CSRF token are rejected | CSRF enforcement on POST |
| 7 | An empty title is rejected by server-side validation | Task title required rule |
| 8 | A valid task can be created and is stored for the signed-in user | Task create + ownership on insert |
| 9 | User-provided content is HTML-escaped to prevent stored XSS | Output escaping of task content |
| 10 | A task can be edited and the changes persist | Task update |
| 11 | Task status can be changed from the dashboard | Inline status change |
| 12 | Invalid status and invalid due-date values are rejected | Enum and date validation |
| 13 | Search returns matching tasks and escaping keeps LIKE input literal | Title search / wildcard escaping |
| 14 | Status filters return only tasks with the selected status | Status filtering |
| 15 | Pagination limits results and clamps out-of-range pages | Page size, page bounds, filter-preserving links |
| 16 | Another user's task cannot be viewed, edited, deleted or have its status changed | Ownership enforced on every task route (404) |
| 17 | Admin can manage accounts but cannot read another user's task content | Admin scope + 404 for foreign task |
| 18 | A standard member cannot access the admin area | Role-based access control (403) |
| 19 | Deactivating an account blocks new and existing sessions | Account deactivation enforced |
| 20 | An administrator cannot deactivate their own account | Self-lockout prevention |
| 21 | Passwords are stored as verifiable hashes, never as plain text | `password_hash` / `password_verify` |
| 22 | A task can be deleted only after a confirmation page | Explicit GET confirmation + CSRF-protected POST delete |
| 23 | Registration creates a hashed member account and signs the user in | Registration flow |
| 24 | Registration rejects duplicate emails and weak passwords | Unique email + password policy |
| 25 | Registration without a CSRF token is rejected | CSRF enforcement on registration |
| 26 | Repeated failed logins are throttled | Login lockout (5 attempts / 15 minutes) |
| 27 | Logout destroys the session and protects the dashboard again | Session invalidation on logout |
| 28 | Unknown routes return 404 and wrong methods return 405 | Router error handling |
| 29 | Dashboard summary numbers agree with the database | Stats correctness |

> The suite uses an isolated database and temporary cookie files. It never touches the demo
> `task_tracker` data and leaves no test database behind.

---

## 2. Manual acceptance checklist

Performed in a browser at 1280 px and 390 px widths against the seeded demo data.

### Authentication and sessions
- [x] `/tasks` while signed out redirects to `/login` with a warning.
- [x] Login with `demo@taskflow.test / Demo@12345` reaches the dashboard.
- [x] Login with a wrong password shows a generic error; the email is preserved.
- [x] Five failed attempts lock the form for 15 minutes with a countdown message.
- [x] Registration with a duplicate email shows an inline error.
- [x] Registration with a short password or mismatched confirmation shows inline errors.
- [x] Logout asks for a POST with CSRF token and destroys the session.
- [x] Accessing `/admin/users` as a member returns 403.
- [x] A deactivated account loses access on its next request.

### Task management
- [x] Create a task with only a title; it appears at the top of the list.
- [x] Create with an empty title shows an inline error and keeps the other values.
- [x] Description with `<script>` is displayed as text, not executed.
- [x] Edit the title/description; the detail page shows the new values.
- [x] Change status from the dashboard; filters and stats update.
- [x] Delete opens a confirmation page naming the task; cancel returns safely.
- [x] Confirming delete removes the task and shows a success message.
- [x] Requesting `/tasks/{id}` for another user's task returns 404.
- [x] Submitting a form without the CSRF token returns 419.

### Search, filter, sort and pagination
- [x] Search narrows results by title, case-insensitively.
- [x] Searching `%` or `_` is treated as literal text, not a wildcard.
- [x] Status filter returns only matching tasks; "All" restores the full list.
- [x] Search + status + sort persist across pagination links.
- [x] Sorting by due date puts undated tasks last.
- [x] Out-of-range `?page=999` clamps to the last page.
- [x] Dashboard shows 8 tasks per page by default.

### Bonus and admin flows
- [x] Summary cards match the visible dataset and the database counts.
- [x] Overdue badge appears only for non-completed tasks with a past due date.
- [x] Admin can deactivate and reactivate a member; the state is reflected in the table.
- [x] Admin cannot deactivate their own account.
- [x] Admin task counts are aggregate only; task content is not exposed.
- [x] Layout works on mobile: drawer navigation, stacked cards, full-width actions.

### Responsiveness and accessibility
- [x] All form controls have associated labels.
- [x] Focus states are visible; errors are announced next to the field.
- [x] Tables scroll or stack without horizontal page overflow.
- [x] No console errors; all assets load from the same origin (CSP-compatible).

---

## 3. Issues found during development and review

The following issues were identified while building and reviewing the project. Each was fixed and,
where practical, covered by a regression test.

| # | Issue | Resolution |
|---:|---|---|
| 1 | **Admin could initially view all tasks.** The first authorization draft let an admin see every user's task list, which weakens the privacy boundary and the brief's ownership requirement. | Replaced with absolute per-owner privacy: every task query filters by the signed-in `user_id` for all roles, and admins are limited to account activation. A regression test was added (#17). |
| 2 | **Wrong base path on Windows.** `SCRIPT_NAME` uses backslashes under the built-in server, producing broken asset URLs. | Normalized `SCRIPT_NAME` and the derived base path to forward slashes in `app/bootstrap.php`, with `.`/`/` handling. |
| 3 | **Incorrect assertion in the delete-flow test.** A test expected a different HTTP status than the application correctly returned after a confirmed delete, producing a false failure. | Verified the application behavior manually and corrected the test assertion. No application change was needed. |
| 4 | **LIKE wildcards in search.** A search for `%` matched every task. | Escaped `!`, `%` and `_` and added `ESCAPE '!'` in `Task::paginateForUser()`. |
| 5 | **Stale CSRF token after login.** A token issued before login could remain valid after session regeneration. | Rotate the CSRF token in `Auth::login()` and `Auth::logout()`. |
| 6 | **Session fixation risk on login.** The session ID was not changed at authentication. | Call `Session::regenerate()` before writing the authenticated user. |
| 7 | **Self-deactivation lockout.** An admin could deactivate their own account and lose access. | Explicit guard in `AdminController` with a clear error message, covered by test #20. |

---

## 4. Reproducing a failure

If the suite fails, it prints the failing test name, expected value and received value, and exits
with a non-zero status. Useful checks:

```bash
php -l tests/run_tests.php
php -m | findstr pdo_mysql   # Windows
php -m | grep -E 'pdo_mysql|curl'
```

- Port `8099` must be free.
- The database user in `.env` must be allowed to create and drop `task_tracker_test`.
- Import `database.sql` into the demo database separately; the suite imports its own copy.
