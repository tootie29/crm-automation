<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Support;

use RichardMedina\CrmAutomation\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class Logger {

	public const SUBDIR = 'rm-ca-logs';

	public static function log_dir(): string {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . self::SUBDIR;
	}

	public static function ensure_log_dir(): void {
		$dir = self::log_dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$index = $dir . '/index.html';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, '' );
		}
		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			@file_put_contents( $htaccess, "Require all denied\nDeny from all\n" );
		}
	}

	public static function info( string $message, array $context = [] ): void {
		self::write( 'info', $message, $context );
		self::db_log( 'info', $message, $context );
	}

	public static function warn( string $message, array $context = [] ): void {
		self::write( 'warn', $message, $context );
		self::db_log( 'warn', $message, $context );
	}

	public static function error( string $message, array $context = [] ): void {
		self::write( 'error', $message, $context );
		self::db_log( 'error', $message, $context );
	}

	private static function write( string $level, string $message, array $context ): void {
		if ( $level === 'info' && ! Settings::get( 'debug_mode', false ) ) {
			return;
		}
		self::ensure_log_dir();
		$file = self::log_dir() . '/crm-' . gmdate( 'Y-m-d' ) . '.log';
		$line = sprintf(
			"[%s] [%s] %s %s\n",
			gmdate( 'c' ),
			strtoupper( $level ),
			$message,
			$context ? wp_json_encode( $context ) : ''
		);
		@file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Hard cap on the JSON context payload stored in the DB to keep the logs table
	 * from ballooning when a form submission carries a large textarea or attachment.
	 * The full payload is still written to the file logger when debug_mode is on.
	 */
	private const DB_CONTEXT_BYTES_MAX = 8192;

	public static function db_log( string $level, string $message, array $context = [] ): void {
		global $wpdb;

		$context_json = null;
		if ( $context ) {
			$encoded = (string) wp_json_encode( $context );
			if ( strlen( $encoded ) > self::DB_CONTEXT_BYTES_MAX ) {
				$encoded = mb_strcut( $encoded, 0, self::DB_CONTEXT_BYTES_MAX - 24 ) . '"…(truncated)"}';
			}
			$context_json = $encoded;
		}

		$table = Schema::logs_table();
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			[
				'rule_id'          => isset( $context['rule_id'] ) ? (int) $context['rule_id'] : null,
				'queue_id'         => isset( $context['queue_id'] ) ? (int) $context['queue_id'] : null,
				'source_type'      => (string) ( $context['source_type'] ?? '' ),
				'source_id'        => (string) ( $context['source_id'] ?? '' ),
				'destination_type' => (string) ( $context['destination_type'] ?? '' ),
				'level'            => $level,
				'message'          => $message,
				'context_json'     => $context_json,
				'created_at'       => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	public static function purge_old(): int {
		global $wpdb;
		$days  = max( 1, (int) Settings::get( 'log_retention_days', 30 ) );
		$table = Schema::logs_table();
		$cut   = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cut ) );
	}
}
