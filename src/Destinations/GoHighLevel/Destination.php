<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Destinations\GoHighLevel;

use RichardMedina\CrmAutomation\Destinations\DestinationContract;
use RichardMedina\CrmAutomation\Destinations\Result;
use RichardMedina\CrmAutomation\Sources\Submission;
use RichardMedina\CrmAutomation\Support\Encryption;
use RichardMedina\CrmAutomation\Support\Http;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class Destination implements DestinationContract {

	public const TYPE     = 'gohighlevel';
	public const BASE_URL = 'https://services.leadconnectorhq.com';
	public const VERSION  = '2021-07-28';

	public function type(): string {
		return self::TYPE;
	}

	public function label(): string {
		return 'GoHighLevel';
	}

	public function target_fields(): array {
		return [
			[ 'key' => 'firstName',   'label' => 'First name' ],
			[ 'key' => 'lastName',    'label' => 'Last name' ],
			[ 'key' => 'name',        'label' => 'Full name' ],
			[ 'key' => 'email',       'label' => 'Email', 'required' => true ],
			[ 'key' => 'phone',       'label' => 'Phone' ],
			[ 'key' => 'address1',    'label' => 'Address line 1' ],
			[ 'key' => 'city',        'label' => 'City' ],
			[ 'key' => 'state',       'label' => 'State' ],
			[ 'key' => 'postalCode',  'label' => 'Postal code' ],
			[ 'key' => 'country',     'label' => 'Country' ],
			[ 'key' => 'website',     'label' => 'Website' ],
			[ 'key' => 'companyName', 'label' => 'Company' ],
			[ 'key' => 'source',      'label' => 'Source' ],
			[ 'key' => 'tags',        'label' => 'Tags (comma-separated)', 'description' => 'Will be split on comma into a tags array.' ],
		];
	}

	public function configured(): bool {
		$d = Settings::destination( self::TYPE );
		return ! empty( $d['token_enc'] ) && ! empty( $d['location_id'] );
	}

	public function send( Submission $submission, array $mapped, array $rule_options = [] ): Result {
		$d           = Settings::destination( self::TYPE );
		$token       = Encryption::decrypt( (string) ( $d['token_enc'] ?? '' ) );
		$location_id = (string) ( $d['location_id'] ?? '' );

		if ( $token === '' || $location_id === '' ) {
			return Result::failure( 0, 'GoHighLevel is not configured (Private Integration Token + Location ID required).', [], false );
		}

		$payload = $this->build_payload( $mapped, $location_id, $rule_options );

		$resp = Http::request( self::BASE_URL . '/contacts/', [
			'method'  => 'POST',
			'timeout' => 20,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Version'       => self::VERSION,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			],
			'body'    => $payload,
		] );

		if ( $resp['ok'] ) {
			return Result::success( $resp['status'], is_array( $resp['body'] ) ? $resp['body'] : [], 'Contact upserted.' );
		}

		// 5xx + 408/429 are retryable. 4xx (validation/auth) are not.
		$retryable = ( $resp['status'] >= 500 ) || in_array( $resp['status'], [ 408, 429 ], true );
		$msg       = is_array( $resp['body'] ) && ! empty( $resp['body']['message'] )
			? (string) $resp['body']['message']
			: ( $resp['error'] ?? 'Unknown error' );

		return Result::failure( $resp['status'], $msg, is_array( $resp['body'] ) ? $resp['body'] : [], $retryable );
	}

	/** @param array<string,string|array<int,string>> $mapped */
	private function build_payload( array $mapped, string $location_id, array $rule_options ): array {
		$payload = [ 'locationId' => $location_id ];

		// Direct copy of canonical scalar fields.
		foreach ( [ 'firstName', 'lastName', 'name', 'email', 'phone', 'address1', 'city', 'state', 'postalCode', 'country', 'website', 'companyName' ] as $k ) {
			$v = $mapped[ $k ] ?? null;
			if ( is_string( $v ) && $v !== '' ) {
				$payload[ $k ] = $v;
			}
		}

		// Source: prefer rule-level static, fall back to mapping.
		$source = (string) ( $rule_options['source'] ?? ( is_string( $mapped['source'] ?? null ) ? $mapped['source'] : '' ) );
		if ( $source === '' ) {
			$source = 'WordPress form';
		}
		$payload['source'] = $source;

		// Tags: combine rule-level + mapped value (split on comma).
		$tags = [];
		if ( ! empty( $rule_options['tags'] ) && is_string( $rule_options['tags'] ) ) {
			$tags = array_merge( $tags, array_filter( array_map( 'trim', explode( ',', $rule_options['tags'] ) ) ) );
		}
		if ( ! empty( $mapped['tags'] ) ) {
			$mapped_tags = is_array( $mapped['tags'] ) ? $mapped['tags'] : explode( ',', (string) $mapped['tags'] );
			$tags        = array_merge( $tags, array_filter( array_map( 'trim', $mapped_tags ) ) );
		}
		if ( $tags ) {
			$payload['tags'] = array_values( array_unique( $tags ) );
		}

		// Custom fields — keys prefixed with cf:
		$custom = [];
		foreach ( $mapped as $key => $value ) {
			if ( ! is_string( $key ) || ! str_starts_with( $key, 'cf:' ) ) {
				continue;
			}
			$custom[] = [
				'key'   => substr( $key, 3 ),
				'field_value' => is_array( $value ) ? implode( ', ', $value ) : (string) $value,
			];
		}
		if ( $custom ) {
			$payload['customFields'] = $custom;
		}

		return $payload;
	}
}
