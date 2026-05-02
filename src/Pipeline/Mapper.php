<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Pipeline;

use RichardMedina\CrmAutomation\Sources\Submission;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves a mapping config (canonical destination key => source spec) into
 * concrete values pulled from a Submission.
 *
 * Mapping config shape (per rule):
 *   [
 *     'firstName' => [ 'mode' => 'field',  'value' => '5.3' ],
 *     'email'     => [ 'mode' => 'field',  'value' => '4'   ],
 *     'source'    => [ 'mode' => 'static', 'value' => 'Website lead' ],
 *     'tags'      => [ 'mode' => 'static', 'value' => 'wp,gravity' ],
 *     'cf:account_type' => [ 'mode' => 'field', 'value' => '7' ],
 *   ]
 */
final class Mapper {

	/**
	 * @param array<string,array{mode:string,value:string}> $mapping
	 * @return array<string,string>
	 */
	public static function map( Submission $submission, array $mapping ): array {
		$out = [];
		foreach ( $mapping as $target_key => $spec ) {
			if ( ! is_string( $target_key ) || $target_key === '' ) {
				continue;
			}
			$mode  = (string) ( $spec['mode'] ?? 'field' );
			$value = (string) ( $spec['value'] ?? '' );

			if ( $mode === 'static' ) {
				if ( $value !== '' ) {
					$out[ $target_key ] = $value;
				}
				continue;
			}

			// mode === 'field' (default)
			if ( $value === '' ) {
				continue;
			}
			$resolved = $submission->field( $value );
			if ( $resolved !== null && $resolved !== '' ) {
				$out[ $target_key ] = $resolved;
			}
		}
		return $out;
	}
}
