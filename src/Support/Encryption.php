<?php
declare( strict_types=1 );

namespace RichardMedina\CrmAutomation\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Symmetric encryption for stored API credentials. Uses libsodium when available
 * (PHP 8.1+ has it), falls back to OpenSSL AES-256-GCM. Key derived from the
 * site's wp_salt('auth') so two sites can't read each other's encrypted values
 * even with the same plugin.
 */
final class Encryption {

	private static function key(): string {
		// Derive a 32-byte key from the auth salt. wp_salt() always returns a string.
		return hash( 'sha256', (string) wp_salt( 'auth' ) . '|rm-ca', true );
	}

	public static function encrypt( string $plaintext ): string {
		if ( $plaintext === '' ) {
			return '';
		}
		$key = self::key();

		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ct    = sodium_crypto_secretbox( $plaintext, $nonce, $key );
			return 'sb1:' . base64_encode( $nonce . $ct );
		}

		$nonce = random_bytes( 12 );
		$tag   = '';
		$ct    = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag );
		if ( $ct === false ) {
			return '';
		}
		return 'gcm1:' . base64_encode( $nonce . $tag . $ct );
	}

	public static function decrypt( string $ciphertext ): string {
		if ( $ciphertext === '' ) {
			return '';
		}
		$key = self::key();

		if ( str_starts_with( $ciphertext, 'sb1:' ) ) {
			$bin = base64_decode( substr( $ciphertext, 4 ), true );
			if ( $bin === false || strlen( $bin ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + 1 ) {
				return '';
			}
			$nonce = substr( $bin, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ct    = substr( $bin, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$pt    = sodium_crypto_secretbox_open( $ct, $nonce, $key );
			return is_string( $pt ) ? $pt : '';
		}

		if ( str_starts_with( $ciphertext, 'gcm1:' ) ) {
			$bin = base64_decode( substr( $ciphertext, 5 ), true );
			if ( $bin === false || strlen( $bin ) < 12 + 16 + 1 ) {
				return '';
			}
			$nonce = substr( $bin, 0, 12 );
			$tag   = substr( $bin, 12, 16 );
			$ct    = substr( $bin, 28 );
			$pt    = openssl_decrypt( $ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag );
			return is_string( $pt ) ? $pt : '';
		}

		return '';
	}

	public static function mask( string $plaintext, int $visible = 4 ): string {
		$len = strlen( $plaintext );
		if ( $len <= $visible ) {
			return str_repeat( '•', $len );
		}
		return str_repeat( '•', $len - $visible ) . substr( $plaintext, -$visible );
	}
}
