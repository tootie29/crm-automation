# CLAUDE.md — RichardMedina CRM Automation

Plugin-specific overrides on top of the agency-wide CLAUDE.md.

## Identity

- Slug: `richardmedina-crm-automation`
- Short prefix: `ca`
- Namespace: `RichardMedina\CrmAutomation`
- Function/hook prefix: `rm_ca_`
- Option key: `rm_ca_settings`
- Tables: `wp_rmca_rules`, `wp_rmca_queue`, `wp_rmca_logs`
- Log dir: `wp-content/uploads/rm-ca-logs/` (htaccess-protected)
- Cron hook: `rm_ca_run_worker` on a custom 60s schedule (`rm_ca_minute`)

## Composer

Not used. Hand-rolled PSR-4 autoloader at `src/Autoloader.php`.

## Multisite

Out of scope for v0.1. Activation refused.

## Hard dependency

Gravity Forms (`class_exists('GFAPI')`). Plugin still loads its admin UI when missing so the dependency notice is reachable, but no source listener is registered.

## Architecture invariants (do not violate)

- **Submission VO is the only thing that crosses the source/destination boundary.** Don't pass Gravity-specific arrays into destinations.
- **Destinations implement `DestinationContract`.** Always return a `Result` value object — never throw or echo.
- **Queue is the only path from source to destination.** No direct `Destination::send()` from a Source. Dispatcher → Queue → Worker → Destination, every time.
- **Credentials are encrypted on the way in (`Encryption::encrypt`) and decrypted only inside the destination.** Never write plaintext API tokens to options or logs.
- **Logger context keys** for log rows: `rule_id`, `queue_id`, `source_type`, `source_id`, `destination_type`. Anything you want filterable in the Logs UI lives here.
- **Retry policy is in `Worker::BACKOFF`** as a single source of truth: 1m, 5m, 30m, 2h, 6h. Adjust there only.

## Scope guardrails for v0.1 (do not exceed without an explicit prompt)

In v0.1 the plugin only does:

- Sources: Gravity Forms only
- Destinations: GoHighLevel + generic webhook
- Field mapping: per rule, `field` mode and `static` mode only
- Queue + retries + logs

Out of scope for v0.1 (do not add unless asked):

- WPForms / Fluent Forms / CF7 sources
- HubSpot / BookingKoala / MaidCentral destinations
- Workflow / multi-step / branching logic
- Field transforms (uppercase, date format, split-name)
- OAuth flows
- Live "fetch CRM custom fields" UI (custom fields are typed by hand in v0.1)
- Email alerts on failure
- Bulk-replay UI for the queue

## When adding a new destination

Implement `DestinationContract`:
1. `type()` — short string, used as DB key
2. `label()` — admin display
3. `target_fields()` — canonical fields the user can map to
4. `configured()` — credential sanity check
5. `send()` — must return `Result::success()` or `Result::failure( $status, $message, $data, $retryable )`

Then register in `Destinations\Registry::all()`.

If credentials are needed, store them under `rm_ca_settings.destinations.<type>` via `Settings::update_destination()`. Always encrypt sensitive values via `Encryption::encrypt()`.

Decide retryability carefully: 5xx/408/429 = retryable; 4xx = not retryable (validation/auth bugs need human intervention, not a backoff loop).

## When adding a new source

Implement `SourceContract`. Hook into the form plugin's "after submission" action (or equivalent). Build a `Submission` VO with all fields normalized to scalar strings and call `Dispatcher::dispatch( $submission )`. That's the entire integration surface.

## Risk rules unique to this plugin

- **Sync vs async**: never call `Destination::send()` synchronously from a form hook. Always go through the queue. A slow CRM must not block form UX.
- **PII in logs**: the `payload_json` in the queue table contains submission data (names, emails, phones). Honor `log_retention_days` to purge regularly. Don't log full API tokens — `Encryption::mask()` for display. The logs table caps `context_json` at 8 KB; the file logger keeps the full payload when `debug_mode` is on.
- **Webhook secret**: if a rule has a secret configured, the request MUST carry the `X-RM-CA-Signature` header. Don't add a "skip if no secret" path on the receiving side that defeats the check. Webhook secrets are stored as `webhook_secret_enc` (encrypted via `Support\Encryption`); plaintext `webhook_secret` is only ever in `$_POST` during save.
- **Webhook URL is an SSRF surface**. `RulesController::is_safe_outbound_url` rejects loopback, link-local, and RFC1918 private targets on save. Don't relax this without an explicit per-rule "allow internal targets" toggle and a documented threat model.

## Encryption key dependency

`Support\Encryption` derives its symmetric key from `wp_salt('auth')`. If `wp-config.php` is leaked (LFI / `.git` exposure / careless backup) the key is recoverable and every stored credential can be decrypted. This is the same threat model as the WP DB password living in `wp-config`. Mitigation paths if you suspect exposure:

1. Rotate the salt (`wp config shuffle-salts`) — invalidates every stored credential AND every login session, so re-enter all CRM tokens / webhook secrets afterward.
2. Audit `Settings::destination()` and the rules table for any plaintext that shouldn't be there.
