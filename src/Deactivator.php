<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation;

defined( 'ABSPATH' ) || exit;

final class Deactivator {

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( Plugin::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, Plugin::CRON_HOOK );
		}
	}
}
