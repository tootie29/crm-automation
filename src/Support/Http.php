<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Support;

defined( 'ABSPATH' ) || exit;

final class Http {

	/**
	 * Wraps wp_remote_request with sane defaults: 15s timeout, JSON, error normalization.
	 *
	 * @param array{
	 *   method?:string,
	 *   headers?:array<string,string>,
	 *   body?:array<mixed>|string|null,
	 *   timeout?:int
	 * } $args
	 *
	 * @return array{ok:bool,status:int,body:array<mixed>|string|null,error:?string,raw:string}
	 */
	public static function request( string $url, array $args = [] ): array {
		$method  = strtoupper( (string) ( $args['method'] ?? 'GET' ) );
		$headers = (array) ( $args['headers'] ?? [] );
		$body    = $args['body'] ?? null;
		$timeout = (int) ( $args['timeout'] ?? 15 );

		$wp_args = [
			'method'  => $method,
			'timeout' => $timeout,
			'headers' => $headers,
		];

		if ( $body !== null ) {
			if ( is_array( $body ) ) {
				if ( ! isset( $headers['Content-Type'] ) ) {
					$wp_args['headers']['Content-Type'] = 'application/json';
				}
				$wp_args['body'] = wp_json_encode( $body );
			} else {
				$wp_args['body'] = (string) $body;
			}
		}

		$response = wp_remote_request( $url, $wp_args );

		if ( is_wp_error( $response ) ) {
			return [
				'ok'     => false,
				'status' => 0,
				'body'   => null,
				'error'  => $response->get_error_message(),
				'raw'    => '',
			];
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw    = (string) wp_remote_retrieve_body( $response );
		$decoded = $raw !== '' ? json_decode( $raw, true ) : null;

		return [
			'ok'     => $status >= 200 && $status < 300,
			'status' => $status,
			'body'   => is_array( $decoded ) ? $decoded : $raw,
			'error'  => ( $status >= 200 && $status < 300 ) ? null : ( "HTTP {$status}" ),
			'raw'    => $raw,
		];
	}
}
