<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Database;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public static function rules_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rmca_rules';
	}

	public static function queue_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rmca_queue';
	}

	public static function logs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rmca_logs';
	}

	public static function install(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$rules = self::rules_table();
		$queue = self::queue_table();
		$logs  = self::logs_table();

		$sql = "
CREATE TABLE {$rules} (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	enabled TINYINT(1) NOT NULL DEFAULT 1,
	name VARCHAR(190) NOT NULL DEFAULT '',
	source_type VARCHAR(64) NOT NULL DEFAULT '',
	source_id VARCHAR(64) NOT NULL DEFAULT '',
	destination_type VARCHAR(64) NOT NULL DEFAULT '',
	mapping_json LONGTEXT NULL,
	options_json LONGTEXT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_source (source_type, source_id),
	KEY idx_enabled (enabled)
) {$charset_collate};

CREATE TABLE {$queue} (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	rule_id BIGINT UNSIGNED NOT NULL,
	status VARCHAR(20) NOT NULL DEFAULT 'pending',
	attempts INT UNSIGNED NOT NULL DEFAULT 0,
	available_at DATETIME NOT NULL,
	payload_json LONGTEXT NOT NULL,
	last_error TEXT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_status_avail (status, available_at),
	KEY idx_rule (rule_id)
) {$charset_collate};

CREATE TABLE {$logs} (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	rule_id BIGINT UNSIGNED NULL,
	queue_id BIGINT UNSIGNED NULL,
	source_type VARCHAR(64) NOT NULL DEFAULT '',
	source_id VARCHAR(64) NOT NULL DEFAULT '',
	destination_type VARCHAR(64) NOT NULL DEFAULT '',
	level VARCHAR(20) NOT NULL DEFAULT 'info',
	message TEXT NOT NULL,
	context_json LONGTEXT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_rule (rule_id),
	KEY idx_created (created_at)
) {$charset_collate};
";

		dbDelta( $sql );
	}

	public static function drop_all(): void {
		global $wpdb;
		foreach ( [ self::rules_table(), self::queue_table(), self::logs_table() ] as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$t}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}
}
