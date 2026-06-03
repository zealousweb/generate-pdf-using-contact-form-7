<?php
/**
 * Shared helpers (password encryption).
 *
 * @package Cf7_Pdf_Generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cf7_Pdf_Global_Settings' ) ) {

	/**
	 * Plugin-wide security helpers.
	 */
	class Cf7_Pdf_Global_Settings {

		/**
		 * @param string $password Plain password.
		 * @return string
		 */
		public static function encrypt_password( $password ) {
			if ( '' === $password ) {
				return '';
			}

			if ( ! function_exists( 'openssl_encrypt' ) ) {
				return base64_encode( $password ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}

			$key = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
			$iv  = substr( hash( 'sha256', wp_salt( 'secure_auth' ) ), 0, 16 );

			$encrypted = openssl_encrypt( $password, 'AES-256-CBC', $key, 0, $iv );

			if ( false === $encrypted ) {
				return '';
			}

			return 'enc:' . base64_encode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		/**
		 * @param string $stored Stored value.
		 * @return string Plain password or empty.
		 */
		public static function decrypt_password( $stored ) {
			if ( '' === $stored || ! is_string( $stored ) ) {
				return '';
			}

			if ( 0 === strpos( $stored, 'enc:' ) ) {
				$payload = base64_decode( substr( $stored, 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				if ( false === $payload || ! function_exists( 'openssl_decrypt' ) ) {
					return '';
				}

				$key = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
				$iv  = substr( hash( 'sha256', wp_salt( 'secure_auth' ) ), 0, 16 );

				$plain = openssl_decrypt( $payload, 'AES-256-CBC', $key, 0, $iv );

				return is_string( $plain ) ? $plain : '';
			}

			return $stored;
		}
	}
}
