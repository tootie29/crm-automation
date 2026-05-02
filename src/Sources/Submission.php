<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Sources;

defined( 'ABSPATH' ) || exit;

/**
 * Normalized submission shape produced by every Source.
 *
 * fields[] entries: { key, label, value, type }
 *   - key:   the source-side field id (e.g. Gravity field id "5", or "5.3" for sub-inputs)
 *   - label: human-readable label
 *   - value: scalar string value (already stringified)
 *   - type:  optional — gravity field type (text/email/phone/...)
 */
final class Submission {

	/**
	 * @param array<int,array{key:string,label:string,value:string,type:string}> $fields
	 * @param array<string,mixed> $meta
	 */
	public function __construct(
		public readonly string $source_type,
		public readonly string $source_id,
		public readonly string $submission_id,
		public readonly array  $fields,
		public readonly array  $meta = [],
	) {}

	public function field( string $key ): ?string {
		foreach ( $this->fields as $f ) {
			if ( $f['key'] === $key ) {
				return $f['value'];
			}
		}
		return null;
	}

	public function to_array(): array {
		return [
			'source_type'   => $this->source_type,
			'source_id'     => $this->source_id,
			'submission_id' => $this->submission_id,
			'fields'        => $this->fields,
			'meta'          => $this->meta,
		];
	}

	public static function from_array( array $a ): self {
		return new self(
			(string) ( $a['source_type'] ?? '' ),
			(string) ( $a['source_id'] ?? '' ),
			(string) ( $a['submission_id'] ?? '' ),
			(array) ( $a['fields'] ?? [] ),
			(array) ( $a['meta'] ?? [] ),
		);
	}
}
