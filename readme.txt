=== RichardMedina CRM Automation ===
Contributors: richardmedina
Tags: gravity-forms, gohighlevel, ghl, crm, webhook, automation
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pipes Gravity Forms submissions into GoHighLevel (or any webhook) with field mapping, async queue, retries, and an audit log.

== Description ==

Internal RichardMedina agency plugin. v0.1 covers:

* **Source:** Gravity Forms
* **Destinations:** GoHighLevel (LeadConnector v2 API, Private Integration Token auth) + generic webhook (POST JSON, optional HMAC signature)
* **Field mapping** with form field or static value modes
* **Async queue** backed by a custom DB table; cron worker every minute with exponential backoff (1m → 5m → 30m → 2h → 6h)
* **Logs** UI showing recent dispatches, failures, retries
* **Encrypted** credential storage (libsodium with OpenSSL fallback)

Single-site only in v0.1.

== Roadmap ==

* v0.2: WPForms + Fluent Forms sources
* v0.2: HubSpot destination (OAuth)
* v0.3: BookingKoala + MaidCentral destinations
* v0.x: workflow steps, conditional logic, transforms

== Installation ==

1. Install Gravity Forms first.
2. Upload `richardmedina-crm-automation/` to `wp-content/plugins/`.
3. Activate.
4. Visit **RM CRM Automation → Destinations** to enter your GHL Private Integration Token + Location ID.
5. Visit **RM CRM Automation → Rules → Add new rule** to wire a form to a destination.

== Changelog ==

= 0.1.1 =
Security: SSRF guard on the webhook URL (rejects loopback, link-local, RFC1918, IPv6 ULA, and non-http(s) schemes; allows the site's own host so dev / self-test setups still work).
Security: webhook shared secret is now encrypted at rest, matching the GHL Private Integration Token.
Security: cap individual Gravity Forms field values at 16 KB before they hit the queue (prevents DoS via giant textarea submissions).
Security: skip Gravity Forms entries flagged as `spam` or `trash` instead of dispatching them to the CRM.
Logs: cap `context_json` to 8 KB in the database log entries to prevent table bloat from large payloads.
GHL: per-rule **Tag prefix** option to namespace tags this rule sends (e.g. `enquiry:` → `enquiry:booking`).
Rule editor: custom-field mapping is now a true repeater — add / remove any number of GHL custom field rows in one save.
Admin UX: shorter sidebar label, polished tables and section cards, empty-state cards on Rules / Logs / Rule editor, distinct form-actions divider.
Bug fix: Destinations form now actually saves the Location ID and encrypted token (previous build's `Settings::sanitize` overwrote `destinations` with stale DB values on every `update_option`).

= 0.1.0 =
Initial release: Gravity Forms → GoHighLevel + Webhook with queue, retries, logs.
