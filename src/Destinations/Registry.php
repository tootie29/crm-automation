<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Destinations;

use RichardMedina\CrmAutomation\Destinations\GoHighLevel\Destination as GoHighLevel;
use RichardMedina\CrmAutomation\Destinations\Webhook\Destination as Webhook;

defined( 'ABSPATH' ) || exit;

final class Registry {

	/** @var array<string,DestinationContract>|null */
	private static ?array $cache = null;

	/** @return array<string,DestinationContract> */
	public static function all(): array {
		if ( self::$cache !== null ) {
			return self::$cache;
		}
		$list  = [
			new GoHighLevel(),
			new Webhook(),
		];
		$cache = [];
		foreach ( $list as $d ) {
			$cache[ $d->type() ] = $d;
		}
		self::$cache = apply_filters( 'rm_ca_destinations', $cache );
		return self::$cache;
	}

	public static function get( string $type ): ?DestinationContract {
		return self::all()[ $type ] ?? null;
	}
}
