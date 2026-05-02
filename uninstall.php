<?php
/**
 * Uninstall handler for RichardMedina CRM Automation.
 * Drops plugin tables, deletes options, removes log directory.
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Autoloader.php';
\RichardMedina\CrmAutomation\Autoloader::register();

\RichardMedina\CrmAutomation\Database\Schema::drop_all();

delete_option( 'rm_ca_settings' );
delete_option( 'rm_ca_version' );

$timestamp = wp_next_scheduled( 'rm_ca_run_worker' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'rm_ca_run_worker' );
}

$uploads = wp_upload_dir( null, false );
if ( ! empty( $uploads['basedir'] ) ) {
	$log_dir = trailingslashit( $uploads['basedir'] ) . 'rm-ca-logs';
	if ( is_dir( $log_dir ) ) {
		$files = glob( $log_dir . '/*' ) ?: [];
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
		foreach ( [ '.htaccess', 'index.html' ] as $hidden ) {
			$p = $log_dir . '/' . $hidden;
			if ( is_file( $p ) ) {
				@unlink( $p );
			}
		}
		@rmdir( $log_dir );
	}
}
