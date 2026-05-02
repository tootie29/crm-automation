<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Destinations;

use RichardMedina\CrmAutomation\Sources\Submission;

defined( 'ABSPATH' ) || exit;

interface DestinationContract {

	public function type(): string;

	public function label(): string;

	/**
	 * Returns the canonical fields a user can map to.
	 *
	 * @return array<int,array{key:string,label:string,required?:bool,description?:string}>
	 */
	public function target_fields(): array;

	/**
	 * Send a mapped payload. The mapping is the result of Mapper::map() —
	 * an associative array of canonical-field-key => stringified-value.
	 *
	 * @param array<string,string|array<int,string>> $mapped
	 * @param array<string,mixed> $rule_options Per-rule destination options (e.g. tags, source).
	 */
	public function send( Submission $submission, array $mapped, array $rule_options = [] ): Result;

	/** Returns true if credentials look configured. */
	public function configured(): bool;
}
