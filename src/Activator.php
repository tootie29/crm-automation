<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation;

use RichardMedina\CrmAutomation\Database\Schema;
use RichardMedina\CrmAutomation\Support\Logger;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class Activator {

	public static function activate(): void {
		if ( is_multisite() ) {
			deactivate_plugins( plugin_basename( RM_CA_FILE ) );
			wp_die(
				esc_html__( 'RichardMedina CRM Automation does not support multisite.', 'richardmedina-crm-automation' ),
				esc_html__( 'Plugin activation failed', 'richardmedina-crm-automation' ),
				[ 'back_link' => true ]
			);
		}

		Schema::install();

		$existing = get_option( Settings::OPTION_KEY, [] );
		if ( ! is_array( $existing ) || empty( $existing ) ) {
			update_option( Settings::OPTION_KEY, Settings::defaults(), false );
		}

		update_option( 'rm_ca_version', RM_CA_VERSION, false );

		Logger::ensure_log_dir();
	}
}
