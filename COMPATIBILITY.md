# COMPATIBILITY

## Tested with

- WordPress: 6.4 – 6.9
- PHP: 8.1, 8.2, 8.3
- Gravity Forms: 2.7+
- GoHighLevel: LeadConnector v2 API (Private Integration Tokens), endpoint version `2021-07-28`

## Required dependencies

- **Gravity Forms** must be active. Without it the plugin loads its admin UI but does not register any source listener; an admin notice on the plugin's screens explains the requirement.

## Known integration notes

- **GoHighLevel:** uses `POST /contacts/` against `https://services.leadconnectorhq.com`. This is an upsert by email — duplicate emails update the existing contact. If you don't want that behavior, send unique emails (e.g. tagged with form id) or use a different endpoint via custom code.
- **GHL Private Integration Token scopes:** the token must include at minimum `Contacts: Write`. Add `Custom Fields: Read` if you want the v0.2 dynamic-field fetcher.
- **Webhook destination:** if a shared secret is configured, requests carry `X-RM-CA-Signature: sha256=<hmac>`. Verify on the receiving end before trusting the payload.
- **Cron:** queue worker runs on `wp_schedule_event` every minute via a custom `rm_ca_minute` interval. Real-world cadence depends on your site's traffic (WP-cron is request-driven). For high reliability, hit `wp-cron.php` from a system cron every minute.

## Known conflicts

_None recorded yet. Update this section when something is observed in the field._

## Recommended companions

- A backup plugin (BlogVault / UpdraftPlus). Allowlist the backup service IPs in **richardmedina-security-hardening** if both plugins are active.
- An external uptime monitor — if your server can't reach the CRM endpoint for an extended period, queue items will retry and eventually go to dead. Knowing about a CRM outage helps explain stuck dead jobs.
