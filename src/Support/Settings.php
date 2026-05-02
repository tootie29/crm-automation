<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Support;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION_KEY = 'rm_ca_settings';

	public static function defaults(): array {
		return [
			'enabled'              => true,
			'debug_mode'           => false,
			'log_retention_days'   => 30,
			'max_attempts'         => 5,

			// Destination credentials are stored encrypted, one entry per destination type.
			'destinations'         => [
				'gohighlevel' => [
					'token_enc'   => '',
					'location_id' => '',
				],
			],
		];
	}

	public static function all(): array {
		$opts = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $opts ) ) {
			$opts = [];
		}
		return self::deep_merge( self::defaults(), $opts );
	}

	/** @param mixed $default */
	public static function get( string $key, $default = null ) {
		$all = self::all();
		return $all[ $key ] ?? $default;
	}

	public static function destination( string $type ): array {
		$all = self::all();
		return is_array( $all['destinations'][ $type ] ?? null ) ? $all['destinations'][ $type ] : [];
	}

	public static function update_destination( string $type, array $data ): void {
		$opts                                = self::all();
		$opts['destinations'][ $type ]       = array_merge( $opts['destinations'][ $type ] ?? [], $data );
		update_option( self::OPTION_KEY, $opts, false );
	}

	/** @param mixed $input */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();
		$clean    = $defaults;

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$clean['enabled']            = ! empty( $input['enabled'] );
		$clean['debug_mode']         = ! empty( $input['debug_mode'] );
		$clean['log_retention_days'] = max( 1, min( 365, (int) ( $input['log_retention_days'] ?? 30 ) ) );
		$clean['max_attempts']       = max( 1, min( 20, (int) ( $input['max_attempts'] ?? 5 ) ) );

		// destinations are written via update_destination() — preserve them.
		$existing = get_option( self::OPTION_KEY, [] );
		$clean['destinations'] = is_array( $existing['destinations'] ?? null ) ? $existing['destinations'] : $defaults['destinations'];

		return $clean;
	}

	private static function deep_merge( array $a, array $b ): array {
		foreach ( $b as $k => $v ) {
			if ( is_array( $v ) && is_array( $a[ $k ] ?? null ) ) {
				$a[ $k ] = self::deep_merge( $a[ $k ], $v );
			} else {
				$a[ $k ] = $v;
			}
		}
		return $a;
	}
}
