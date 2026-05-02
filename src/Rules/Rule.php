<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Rules;

defined( 'ABSPATH' ) || exit;

final class Rule {

	/**
	 * @param array<string,array{mode:string,value:string}> $mapping
	 * @param array<string,mixed> $options
	 */
	public function __construct(
		public readonly int    $id,
		public readonly bool   $enabled,
		public readonly string $name,
		public readonly string $source_type,
		public readonly string $source_id,
		public readonly string $destination_type,
		public readonly array  $mapping,
		public readonly array  $options,
	) {}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		$mapping = json_decode( (string) ( $row['mapping_json'] ?? '' ), true );
		$options = json_decode( (string) ( $row['options_json'] ?? '' ), true );
		return new self(
			(int) ( $row['id'] ?? 0 ),
			(int) ( $row['enabled'] ?? 0 ) === 1,
			(string) ( $row['name'] ?? '' ),
			(string) ( $row['source_type'] ?? '' ),
			(string) ( $row['source_id'] ?? '' ),
			(string) ( $row['destination_type'] ?? '' ),
			is_array( $mapping ) ? $mapping : [],
			is_array( $options ) ? $options : [],
		);
	}

	public function to_db_row(): array {
		return [
			'enabled'          => $this->enabled ? 1 : 0,
			'name'             => $this->name,
			'source_type'      => $this->source_type,
			'source_id'        => $this->source_id,
			'destination_type' => $this->destination_type,
			'mapping_json'     => (string) wp_json_encode( $this->mapping ),
			'options_json'     => (string) wp_json_encode( $this->options ),
		];
	}
}
