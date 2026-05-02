<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Destinations\Webhook;

use RichardMedina\CrmAutomation\Destinations\DestinationContract;
use RichardMedina\CrmAutomation\Destinations\Result;
use RichardMedina\CrmAutomation\Sources\Submission;
use RichardMedina\CrmAutomation\Support\Http;

defined( 'ABSPATH' ) || exit;

/**
 * Generic webhook-out destination. Per-rule URL is provided via rule options
 * so multiple rules can each fan out to different endpoints (Zapier / Make /
 * n8n / a custom server).
 */
final class Destination implements DestinationContract {

	public const TYPE = 'webhook';

	public function type(): string {
		return self::TYPE;
	}

	public function label(): string {
		return 'Generic webhook (POST JSON)';
	}

	public function target_fields(): array {
		return [
			[ 'key' => 'firstName',  'label' => 'First name' ],
			[ 'key' => 'lastName',   'label' => 'Last name' ],
			[ 'key' => 'email',      'label' => 'Email' ],
			[ 'key' => 'phone',      'label' => 'Phone' ],
			[ 'key' => 'address',    'label' => 'Address' ],
			[ 'key' => 'city',       'label' => 'City' ],
			[ 'key' => 'state',      'label' => 'State' ],
			[ 'key' => 'postalCode', 'label' => 'Postal code' ],
			[ 'key' => 'country',    'label' => 'Country' ],
			[ 'key' => 'message',    'label' => 'Message / notes' ],
			[ 'key' => 'tags',       'label' => 'Tags' ],
		];
	}

	public function configured(): bool {
		// Configured per-rule via rule options. Globally always usable.
		return true;
	}

	public function send( Submission $submission, array $mapped, array $rule_options = [] ): Result {
		$url = (string) ( $rule_options['webhook_url'] ?? '' );
		if ( $url === '' || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return Result::failure( 0, 'Webhook destination requires a valid URL on the rule.', [], false );
		}

		$secret = (string) ( $rule_options['webhook_secret'] ?? '' );

		$payload = [
			'mapped'     => $mapped,
			'submission' => $submission->to_array(),
			'sent_at'    => gmdate( 'c' ),
		];

		$body = wp_json_encode( $payload );

		$headers = [ 'Content-Type' => 'application/json' ];
		if ( $secret !== '' ) {
			$headers['X-RM-CA-Signature'] = 'sha256=' . hash_hmac( 'sha256', (string) $body, $secret );
		}

		$resp = Http::request( $url, [
			'method'  => 'POST',
			'timeout' => 20,
			'headers' => $headers,
			'body'    => $body,
		] );

		if ( $resp['ok'] ) {
			return Result::success( $resp['status'], is_array( $resp['body'] ) ? $resp['body'] : [], 'Webhook accepted.' );
		}

		$retryable = ( $resp['status'] === 0 ) || ( $resp['status'] >= 500 ) || in_array( $resp['status'], [ 408, 429 ], true );
		return Result::failure( $resp['status'], $resp['error'] ?? 'Webhook failed', is_array( $resp['body'] ) ? $resp['body'] : [], $retryable );
	}
}
