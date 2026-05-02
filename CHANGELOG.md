# Changelog

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
