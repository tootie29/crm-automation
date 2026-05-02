# Changelog

## 0.1.1 — 2026-05-03

### Security
- **H2 fix:** SSRF guard on the webhook destination URL. `RulesController::is_safe_outbound_url()` rejects, on save, non-http(s) schemes, `localhost` / `*.localhost` / `*.local`, IPv4 literals in loopback / link-local / RFC1918, and IPv6 loopback / ULA / non-global ranges. Same-host URLs (matching `home_url()` host) are explicitly allowed so LocalWP / dev / self-test setups still work.
- **M1 fix:** `Logger::db_log` truncates `context_json` to 8 KB before insert (with a `(truncated)` marker) so a single oversize payload can't bloat the log table. Full payload still hits the file logger when `debug_mode` is on.
- **M2 fix:** webhook shared secret is now encrypted at rest via `Support\Encryption` (libsodium → AES-GCM fallback). Stored as `webhook_secret_enc`; field rendered as `type="password"` with a "Secret saved (encrypted)" placeholder on edit.
- **M3:** `CLAUDE.md` documents that `Support\Encryption` derives its key from `wp_salt('auth')`, with a salt-rotation recovery path for suspected `wp-config.php` exposure.
- **GF1:** cap individual Gravity Forms field values at 16 KB in `Source::collect_fields` (with a clear truncation marker). Prevents DoS via giant textarea submissions bloating the queue's `payload_json` LONGTEXT column.
- **GF2:** skip Gravity Forms entries with `status = spam` or `status = trash` instead of dispatching them to the CRM.
- **GF3:** plugin `CLAUDE.md` documents that `$entry['ip']` (from `GFFormsModel::get_ip()`) is X-Forwarded-For-trusting and must never be used for security decisions.

### Bug fixes
- Destinations form now actually saves the Location ID and encrypted token. Previous build's `Settings::sanitize` ran via `register_setting`'s `sanitize_option_*` hook on every `update_option` and unconditionally read the existing `destinations` array from the DB — overwriting the new write before it could land. Fixed by trusting `$input['destinations']` when present (covers `update_destination()` and any direct `update_option` writer) and only reading from the DB when the input has no `destinations` key (the SettingsPage form path).

### Features
- **GHL tag prefix:** new per-rule option that prepends a verbatim string to every tag the rule sends (static + mapped). Lets agencies safely route multiple form sources into the same GHL location without tag collisions.
- **Custom field repeater:** the rule editor's GHL custom-field mapper is now a real repeater. Add / edit / remove any number of `cf:*` mappings, save once.

### Admin UX
- Shorter sidebar label (`RM Automation`) so it doesn't wrap.
- Polished tables (Rules, Logs, Mapping) with rounded borders, uppercase muted column headers, hover highlight, inline `code` badges with light pill backgrounds.
- Empty-state cards on Rules list, Logs list, and the rule editor's "no source fields yet" message.
- Form-actions row gets a top divider so it reads as a distinct commit step.
- Webhook secret field rendered as password input with a saved-state placeholder.

## 0.1.0 — 2026-05-03

Initial release.

- Source adapter: Gravity Forms (`gform_after_submission`)
- Destinations: GoHighLevel (LeadConnector v2 API + Private Integration Token) and generic Webhook (POST JSON, optional HMAC signature)
- Field mapping per rule with `field` and `static` modes; first-class GHL custom fields via `cf:` prefix
- Async queue backed by `wp_rmca_queue` with cron worker (every minute) and exponential backoff retries (1m / 5m / 30m / 2h / 6h)
- Encrypted credential storage (libsodium with OpenSSL AES-256-GCM fallback)
- Admin UI: Rules list + editor, Destinations, Logs (with queue counts overview), Settings
- Custom DB schema: `wp_rmca_rules`, `wp_rmca_queue`, `wp_rmca_logs`
- Logs persisted both to `wp_rmca_logs` and to file under `uploads/rm-ca-logs/` (htaccess-protected)
- Single-site only; activation refused on multisite
