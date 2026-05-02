<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Admin;

use RichardMedina\CrmAutomation\Support\Encryption;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class DestinationsController {

	public function register(): void {
		add_action( 'admin_post_rm_ca_save_destinations', [ $this, 'save' ] );
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'richardmedina-crm-automation' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( DestinationsPage::NONCE );

		$ghl_in      = (array) ( $_POST['ghl'] ?? [] );
		$location_id = sanitize_text_field( (string) ( $ghl_in['location_id'] ?? '' ) );
		$new_token   = (string) ( $ghl_in['token'] ?? '' );

		$update = [ 'location_id' => $location_id ];
		if ( $new_token !== '' ) {
			$update['token_enc'] = Encryption::encrypt( $new_token );
		}
		Settings::update_destination( 'gohighlevel', $update );

		wp_safe_redirect( add_query_arg( [
			'page'    => AdminBoot::PAGE_SLUG . '-destinations',
			'updated' => '1',
		], admin_url( 'admin.php' ) ) );
		exit;
	}
}
