<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Rules;

use RichardMedina\CrmAutomation\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class Repository {

	public static function find( int $id ): ?Rule {
		global $wpdb;
		$table = Schema::rules_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? Rule::from_row( $row ) : null;
	}

	/** @return array<int,Rule> */
	public static function all(): array {
		global $wpdb;
		$table = Schema::rules_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) {
			$out[] = Rule::from_row( $row );
		}
		return $out;
	}

	/** @return array<int,Rule> */
	public static function find_for_source( string $source_type, string $source_id ): array {
		global $wpdb;
		$table = Schema::rules_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE enabled = 1 AND source_type = %s AND source_id = %s",
			$source_type,
			$source_id
		), ARRAY_A );
		$out = [];
		foreach ( (array) $rows as $row ) {
			$out[] = Rule::from_row( $row );
		}
		return $out;
	}

	public static function save( Rule $rule ): int {
		global $wpdb;
		$table = Schema::rules_table();
		$data  = $rule->to_db_row();

		if ( $rule->id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, $data, [ 'id' => $rule->id ], null, [ '%d' ] );
			return $rule->id;
		}
		$data['created_at'] = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $table, $data );
		return (int) $wpdb->insert_id;
	}

	public static function delete( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( Schema::rules_table(), [ 'id' => $id ], [ '%d' ] );
	}

	public static function set_enabled( int $id, bool $enabled ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( Schema::rules_table(), [ 'enabled' => $enabled ? 1 : 0 ], [ 'id' => $id ], [ '%d' ], [ '%d' ] );
	}
}
