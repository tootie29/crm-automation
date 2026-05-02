<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Destinations;

defined( 'ABSPATH' ) || exit;

final class Result {

	public function __construct(
		public readonly bool   $ok,
		public readonly int    $status,
		public readonly string $message = '',
		public readonly array  $data = [],
		public readonly bool   $retryable = false,
	) {}

	public static function success( int $status = 200, array $data = [], string $message = '' ): self {
		return new self( true, $status, $message, $data, false );
	}

	public static function failure( int $status, string $message, array $data = [], bool $retryable = false ): self {
		return new self( false, $status, $message, $data, $retryable );
	}
}
