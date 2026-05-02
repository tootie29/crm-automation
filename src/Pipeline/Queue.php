<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Pipeline;

use RichardMedina\CrmAutomation\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class Queue {

	public const STATUS_PENDING = 'pending';
	public const STATUS_RUNNING = 'running';
	public const STATUS_DONE    = 'done';
	public const STATUS_FAILED  = 'failed';
	public const STATUS_DEAD    = 'dead';

	public static function enqueue( int $rule_id, array $payload, int $delay_seconds = 0 ): int {
		global $wpdb;
		$table = Schema::queue_table();
		$ts    = gmdate( 'Y-m-d H:i:s', time() + max( 0, $delay_seconds ) );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			[
				'rule_id'      => $rule_id,
				'status'       => self::STATUS_PENDING,
				'attempts'     => 0,
				'available_at' => $ts,
				'payload_json' => (string) wp_json_encode( $payload ),
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%d', '%s', '%s', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Pull up to $limit pending jobs whose available_at <= now and lock them as running.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function lock_batch( int $limit = 10 ): array {
		global $wpdb;
		$table = Schema::queue_table();
		$now   = current_time( 'mysql', true );

		// Fetch eligible ids first (FOR UPDATE not portable across hosts).
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE status = %s AND available_at <= %s ORDER BY available_at ASC LIMIT %d",
			self::STATUS_PENDING,
			$now,
			$limit
		) );

		if ( empty( $ids ) ) {
			return [];
		}

		$ids       = array_map( 'intval', $ids );
		$ids_csv   = implode( ',', $ids );
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET status = %s WHERE id IN ({$ids_csv})",
			self::STATUS_RUNNING
		) );

		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE id IN ({$ids_csv}) ORDER BY id ASC", ARRAY_A );
		// phpcs:enable

		return is_array( $rows ) ? $rows : [];
	}

	public static function complete( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( Schema::queue_table(), [ 'status' => self::STATUS_DONE ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
	}

	public static function reschedule( int $id, int $attempts, int $delay_seconds, string $error ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			Schema::queue_table(),
			[
				'status'       => self::STATUS_PENDING,
				'attempts'     => $attempts,
				'available_at' => gmdate( 'Y-m-d H:i:s', time() + max( 0, $delay_seconds ) ),
				'last_error'   => $error,
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public static function dead( int $id, string $error ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			Schema::queue_table(),
			[ 'status' => self::STATUS_DEAD, 'last_error' => $error ],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	public static function counts_by_status(): array {
		global $wpdb;
		$table = Schema::queue_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS n FROM {$table} GROUP BY status", ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $r ) {
			$out[ $r['status'] ] = (int) $r['n'];
		}
		return $out;
	}
}
