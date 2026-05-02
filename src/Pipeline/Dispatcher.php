<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Pipeline;

use RichardMedina\CrmAutomation\Rules\Repository as RuleRepository;
use RichardMedina\CrmAutomation\Sources\Submission;
use RichardMedina\CrmAutomation\Support\Logger;
use RichardMedina\CrmAutomation\Support\Settings;

defined( 'ABSPATH' ) || exit;

final class Dispatcher {

	/**
	 * Find every enabled rule that matches a submission, enqueue a job per rule.
	 */
	public static function dispatch( Submission $submission ): void {
		if ( ! Settings::get( 'enabled', true ) ) {
			Logger::info( 'dispatcher.skipped_master_off', [
				'source_type' => $submission->source_type,
				'source_id'   => $submission->source_id,
			] );
			return;
		}

		$rules = RuleRepository::find_for_source( $submission->source_type, $submission->source_id );
		if ( empty( $rules ) ) {
			Logger::info( 'dispatcher.no_matching_rule', [
				'source_type' => $submission->source_type,
				'source_id'   => $submission->source_id,
			] );
			return;
		}

		foreach ( $rules as $rule ) {
			$payload = [
				'submission' => $submission->to_array(),
				'rule'       => [
					'id'      => $rule->id,
					'name'    => $rule->name,
				],
			];

			$queue_id = Queue::enqueue( $rule->id, $payload );

			Logger::info( 'dispatcher.enqueued', [
				'rule_id'          => $rule->id,
				'queue_id'         => $queue_id,
				'source_type'      => $submission->source_type,
				'source_id'        => $submission->source_id,
				'destination_type' => $rule->destination_type,
			] );
		}
	}
}
