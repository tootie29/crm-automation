<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Rules\Rule;

defined( 'ABSPATH' ) || exit;

final class RulesController {

	public function register(): void {
		add_action( 'admin_post_rm_ca_save_rule', [ $this, 'save' ] );
		add_action( 'admin_init', [ $this, 'maybe_handle_inline_actions' ] );
	}

	public function maybe_handle_inline_actions(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== AdminBoot::PAGE_SLUG ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['rm_ca_action'] ) ? sanitize_key( (string) $_GET['rm_ca_action'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rule_id = isset( $_GET['rule_id'] ) ? (int) $_GET['rule_id'] : 0;

		if ( $action === '' || $rule_id <= 0 ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $action === 'toggle' ) {
			check_admin_referer( 'rm_ca_toggle_' . $rule_id );
			$rule = RuleRepository::find( $rule_id );
			if ( $rule ) {
				RuleRepository::set_enabled( $rule_id, ! $rule->enabled );
			}
		} elseif ( $action === 'delete' ) {
			check_admin_referer( 'rm_ca_delete_' . $rule_id );
			RuleRepository::delete( $rule_id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . AdminBoot::PAGE_SLUG ) );
		exit;
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'richardmedina-crm-automation' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( RuleEditor::NONCE );

		$rule_id          = (int) ( $_POST['rule_id'] ?? 0 );
		$name             = sanitize_text_field( (string) ( $_POST['name'] ?? '' ) );
		$enabled          = ! empty( $_POST['enabled'] );
		$source_type      = sanitize_key( (string) ( $_POST['source_type'] ?? '' ) );
		$source_id        = sanitize_text_field( (string) ( $_POST['source_id'] ?? '' ) );
		$destination_type = sanitize_key( (string) ( $_POST['destination_type'] ?? '' ) );

		// Mapping: only keep keys with a mode + value, normalize.
		$mapping_in = (array) ( $_POST['mapping'] ?? [] );
		$mapping    = [];
		foreach ( $mapping_in as $key => $spec ) {
			if ( ! is_string( $key ) || $key === '' || ! is_array( $spec ) ) {
				continue;
			}
			$mode  = (string) ( $spec['mode'] ?? 'field' );
			$value = $mode === 'static'
				? sanitize_text_field( (string) ( $spec['static_value'] ?? '' ) )
				: sanitize_text_field( (string) ( $spec['value'] ?? '' ) );

			if ( ! in_array( $mode, [ 'field', 'static' ], true ) || $value === '' ) {
				continue;
			}
			$mapping[ $key ] = [ 'mode' => $mode, 'value' => $value ];
		}

		// Repeater: custom GHL fields, one row each.
		$custom_in = (array) ( $_POST['custom_fields'] ?? [] );
		foreach ( $custom_in as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$ck   = sanitize_text_field( (string) ( $row['key'] ?? '' ) );
			$mode = (string) ( $row['mode'] ?? 'field' );
			$cv   = $mode === 'static'
				? sanitize_text_field( (string) ( $row['static_value'] ?? '' ) )
				: sanitize_text_field( (string) ( $row['value'] ?? '' ) );

			if ( $ck === '' || $cv === '' || ! in_array( $mode, [ 'field', 'static' ], true ) ) {
				continue; // drop incomplete rows silently
			}
			// Strip any user-supplied "cf:" prefix so we don't end up with cf:cf:foo.
			$ck = preg_replace( '/^cf:/i', '', $ck );
			if ( $ck === '' ) {
				continue;
			}
			$mapping[ 'cf:' . $ck ] = [ 'mode' => $mode, 'value' => $cv ];
		}

		// Per-rule destination options.
		$opts_in = (array) ( $_POST['options'] ?? [] );
		$options = [];
		foreach ( [ 'webhook_url', 'webhook_secret', 'source', 'tags', 'tag_prefix' ] as $k ) {
			if ( isset( $opts_in[ $k ] ) ) {
				$options[ $k ] = sanitize_text_field( (string) $opts_in[ $k ] );
			}
		}
		if ( isset( $options['webhook_url'] ) && $options['webhook_url'] !== '' ) {
			$options['webhook_url'] = esc_url_raw( $options['webhook_url'] );
			if ( ! self::is_safe_outbound_url( $options['webhook_url'] ) ) {
				wp_die(
					esc_html__( 'Webhook URL must be a public http(s) URL. Loopback, link-local, and private network targets (127.0.0.1, 10.x, 172.16-31.x, 192.168.x, 169.254.x, IPv6 equivalents) are not allowed — they would let a misconfigured rule POST form data into internal services.', 'richardmedina-crm-automation' ),
					esc_html__( 'Unsafe webhook URL', 'richardmedina-crm-automation' ),
					[ 'response' => 400, 'back_link' => true ]
				);
			}
		}

		// Encrypt the webhook secret for symmetry with the GHL token. Plaintext entries
		// (legacy or freshly typed by the admin) get encrypted on save; the worker
		// transparently decrypts before signing. Empty submission preserves the prior
		// encrypted value — same UX as the GHL token field.
		if ( isset( $options['webhook_secret'] ) && $options['webhook_secret'] !== '' ) {
			$options['webhook_secret_enc'] = \RichardMedina\CrmAutomation\Support\Encryption::encrypt( $options['webhook_secret'] );
		} else {
			$existing = $rule_id > 0 ? RuleRepository::find( $rule_id ) : null;
			if ( $existing && ! empty( $existing->options['webhook_secret_enc'] ) ) {
				$options['webhook_secret_enc'] = (string) $existing->options['webhook_secret_enc'];
			}
		}
		unset( $options['webhook_secret'] );

		$rule = new Rule( $rule_id, $enabled, $name, $source_type, $source_id, $destination_type, $mapping, $options );
		$saved_id = RuleRepository::save( $rule );

		wp_safe_redirect( add_query_arg( [
			'page'    => AdminBoot::PAGE_SLUG,
			'action'  => 'edit',
			'rule_id' => $saved_id,
			'updated' => '1',
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * SSRF guard for the webhook destination URL. Rejects:
	 *  - non-http(s) schemes
	 *  - hostname literals "localhost" / *.localhost / *.local
	 *  - IPv4 literals in loopback, link-local, multicast, broadcast, or RFC1918 private space
	 *  - IPv6 literals that are loopback, link-local, ULA, or otherwise non-public
	 *
	 * Hostnames that are *names* (not literal IPs) are not DNS-resolved here — DNS-rebinding
	 * is still possible at request time. This validator catches the common operator mistake of
	 * pasting a private URL; defense-in-depth (e.g. an outbound network policy) handles the rest.
	 */
	private static function is_safe_outbound_url( string $url ): bool {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}
		if ( ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
			return false;
		}

		$host = strtolower( $parts['host'] );

		// Allow the site's own host even if it falls under .local / .localhost or a private IP —
		// the admin can already POST to their own site, so allowing self-hostname adds no SSRF
		// risk beyond what's already possible. Important for LocalWP / dev / self-test setups.
		$home_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		if ( $home_host !== '' && $host === $home_host ) {
			return true;
		}

		if ( $host === 'localhost' || str_ends_with( $host, '.localhost' ) || str_ends_with( $host, '.local' ) ) {
			return false;
		}

		// Hostname is a name (not an IP literal): we accept it here. DNS-rebinding remains a
		// theoretical risk but is out of scope for v0.1's URL validator.
		$ip_str = trim( $host, '[]' );
		if ( filter_var( $ip_str, FILTER_VALIDATE_IP ) === false ) {
			return true;
		}

		// Reject any IP literal that isn't in the public Internet ranges.
		$public = filter_var(
			$ip_str,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
		return $public !== false;
	}
}
