# Changelog

All notable changes to B2B Visitor Intelligence are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.0.0] — 2026-03-30

First stable release. Major refactor from internal prototype.

### Added
- `uninstall.php` — drops all plugin tables and options on deletion.
- `includes/class-activator.php` — dedicated activation class.
- `includes/class-deactivator.php` — dedicated deactivation class.
- `docs/` folder — installation, configuration, hooks reference, and changelog.
- `languages/` folder with `.pot` template for translation readiness.
- PHP 7.4 minimum version gate with admin notice on old hosts.
- `BVIP_PLUGIN_FILE` constant for reliable lifecycle hook registration.
- Admin-only loading of `admin/class-dashboard.php` via `is_admin()` guard.

### Fixed
- **Critical:** `/track` REST endpoint did not return `visit_id` or `session_hash`.
  This silently broke duration tracking and click-session linking for all visitors.
- **Critical:** `waitForSession` IIFE in `tracker.js` was outside the main IIFE scope,
  so `sessionHash` was always `undefined` — `window._bvipSessionHash` was never set,
  breaking click-to-session association. Now set directly inside the fetch callback.
- **Critical:** `sendBeacon` sends `text/plain` content-type by default; WordPress REST
  API only JSON-decodes `application/json` requests. All `sendBeacon` calls now wrap
  the payload in a `Blob` with `type: 'application/json'`.
- **Security:** `ajax_realtime` and `ajax_flush_cache` checked nonces but not user
  capability. Any logged-in user could call them. Added `current_user_can('manage_options')`.
- **Security:** `$_SERVER['HTTP_HOST']` in `class-click-tracker.php` is attacker-controlled
  via HTTP Host header. Replaced with `home_url()`.
- **Bug:** `max()` called on result of `array_column()` which could be empty in
  `admin/views/duration.php`, causing PHP warning and division by zero.
- **Bug:** `echo count($companies)` in `admin/views/dashboard.php` was not escaped.
- **Race condition:** In `class-alerts.php`, the alert transient was set *after* sending
  the email. Parallel cron runs could both pass the `get_transient` check and send
  duplicate alerts. Transient is now set before sending.
- **SQL:** `flush_cache()` used raw string literals in a DELETE query. Now uses
  `$wpdb->prepare()` with `$wpdb->esc_like()`.

### Changed
- Plugin version unified to `1.0.0` (header previously said `0.0.1`, constant said `2.0.0`).
- Added proper plugin headers: `Requires at least`, `Requires PHP`, `License URI`, `Domain Path`.
- Bootstrap file (`b2b-visitor-intelligence.php`) now only loads admin code on `is_admin()`.
- Activation/deactivation hooks now reference `BVIP_PLUGIN_FILE` constant instead of
  `__FILE__` inside an included file (more reliable).

---

## [0.0.1] — Initial prototype

Internal prototype. Not publicly released.
