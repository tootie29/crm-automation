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

		// Optional custom-field row.
		$custom_key    = sanitize_text_field( (string) ( $_POST['custom_key'] ?? '' ) );
		$custom_source = sanitize_text_field( (string) ( $_POST['custom_source'] ?? '' ) );
		if ( $custom_key !== '' && $custom_source !== '' ) {
			$mapping[ 'cf:' . $custom_key ] = [ 'mode' => 'field', 'value' => $custom_source ];
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
		}

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
}
