<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Sources;

defined( 'ABSPATH' ) || exit;

interface SourceContract {

	public function type(): string;

	public function label(): string;

	public function register(): void;

	/**
	 * List available sources of this type — e.g. all Gravity Forms forms.
	 *
	 * @return array<int,array{id:string,label:string}>
	 */
	public function list(): array;

	/**
	 * Describe the fields of a given source — e.g. fields of Gravity form 5.
	 *
	 * @return array<int,array{key:string,label:string,type:string}>
	 */
	public function describe_fields( string $source_id ): array;
}
