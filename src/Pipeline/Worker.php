<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Pipeline;

use RichardMedina\CrmAutomation\Destinations\Registry as DestinationRegistry;
use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Sources\Submission;
use RichardMedina\CrmAutomation\Support\Logger;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class Worker {

	/** Backoff schedule per attempt (seconds). 1m, 5m, 30m, 2h, 6h. */
	private const BACKOFF = [ 60, 300, 1800, 7200, 21600 ];

	public static function run(): void {
		$batch = Queue::lock_batch( 10 );
		if ( empty( $batch ) ) {
			return;
		}

		foreach ( $batch as $row ) {
			self::process( $row );
		}
	}

	/** @param array<string,mixed> $row */
	private static function process( array $row ): void {
		$queue_id = (int) $row['id'];
		$rule_id  = (int) $row['rule_id'];
		$attempts = (int) $row['attempts'] + 1;
		$max      = max( 1, (int) Settings::get( 'max_attempts', 5 ) );

		$payload = json_decode( (string) $row['payload_json'], true );
		if ( ! is_array( $payload ) ) {
			Queue::dead( $queue_id, 'Corrupt payload' );
			Logger::error( 'worker.corrupt_payload', [ 'queue_id' => $queue_id, 'rule_id' => $rule_id ] );
			return;
		}

		$rule = RuleRepository::find( $rule_id );
		if ( ! $rule || ! $rule->enabled ) {
			Queue::dead( $queue_id, 'Rule missing or disabled' );
			Logger::warn( 'worker.rule_missing_or_disabled', [ 'queue_id' => $queue_id, 'rule_id' => $rule_id ] );
			return;
		}

		$destination = DestinationRegistry::get( $rule->destination_type );
		if ( ! $destination ) {
			Queue::dead( $queue_id, 'Unknown destination type: ' . $rule->destination_type );
			Logger::error( 'worker.unknown_destination', [ 'queue_id' => $queue_id, 'rule_id' => $rule_id, 'type' => $rule->destination_type ] );
			return;
		}

		$submission = Submission::from_array( (array) ( $payload['submission'] ?? [] ) );
		$mapped     = Mapper::map( $submission, $rule->mapping );

		$result = $destination->send( $submission, $mapped, $rule->options );

		$ctx = [
			'queue_id'         => $queue_id,
			'rule_id'          => $rule_id,
			'source_type'      => $submission->source_type,
			'source_id'        => $submission->source_id,
			'destination_type' => $rule->destination_type,
			'http_status'      => $result->status,
			'attempts'         => $attempts,
		];

		if ( $result->ok ) {
			Queue::complete( $queue_id );
			Logger::info( 'worker.success: ' . $result->message, $ctx );
			return;
		}

		if ( ! $result->retryable || $attempts >= $max ) {
			Queue::dead( $queue_id, $result->message );
			Logger::error( 'worker.dead: ' . $result->message, array_merge( $ctx, [ 'response' => $result->data ] ) );
			return;
		}

		$delay = self::BACKOFF[ min( count( self::BACKOFF ) - 1, $attempts - 1 ) ];
		Queue::reschedule( $queue_id, $attempts, $delay, $result->message );
		Logger::warn( 'worker.retry_scheduled: ' . $result->message, array_merge( $ctx, [ 'next_in_seconds' => $delay ] ) );
	}

	/** Manual single-job retry — moves a failed/dead job back to pending. */
	public static function retry_now( int $queue_id ): bool {
		Queue::reschedule( $queue_id, 0, 0, '' );
		return true;
	}
}
