=== RichardMedina CRM Automation ===
Contributors: richardmedina
Tags: gravity-forms, gohighlevel, ghl, crm, webhook, automation
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.0
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

= 0.1.0 =
Initial release: Gravity Forms → GoHighLevel + Webhook with queue, retries, logs.
