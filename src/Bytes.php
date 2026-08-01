<?php
/**
 * Byte sizes.
 *
 * @package   SugarMill\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarMill\Format;

/**
 * Class Bytes
 *
 * Formats and parses byte counts.
 *
 * Two conventions exist and both are correct depending on who is asking.
 * **Binary** (1 KB = 1024 bytes) is what a filesystem reports and what
 * an upload limit means. **Decimal** (1 kB = 1000 bytes) is what a disk
 * manufacturer prints on the box and what a network transfer rate uses.
 * Mixing them is where "why does my 500 GB drive show 465 GB?" comes
 * from. Binary is the default here because this is usually describing a
 * file.
 *
 * @since 1.0.0
 */
final readonly class Bytes {

	/**
	 * Binary unit names, ascending.
	 */
	private const BINARY = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );

	/**
	 * Decimal unit names, ascending.
	 */
	private const DECIMAL = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB' );

	/**
	 * Multipliers accepted when parsing, keyed by lowercase unit.
	 */
	private const MULTIPLIERS = array(
		'b' => 1,
		'k' => 1024, 'kb' => 1024, 'kib' => 1024,
		'm' => 1048576, 'mb' => 1048576, 'mib' => 1048576,
		'g' => 1073741824, 'gb' => 1073741824, 'gib' => 1073741824,
		't' => 1099511627776, 'tb' => 1099511627776, 'tib' => 1099511627776,
		'p' => 1125899906842624, 'pb' => 1125899906842624, 'pib' => 1125899906842624,
	);

	/**
	 * Format a byte count.
	 *
	 * A value within 5% of a whole unit prints without a decimal, so a
	 * 104,857,600-byte file reads as `100 MB` rather than `100.0 MB`.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $bytes   Byte count. Negative values keep their sign.
	 * @param bool $decimal Use decimal units (1 kB = 1000 bytes).
	 *
	 * @return string
	 */
	public static function format( int $bytes, bool $decimal = false ): string {
		$sign  = $bytes < 0 ? '-' : '';
		$value = abs( $bytes );
		$step  = $decimal ? 1000 : 1024;
		$units = $decimal ? self::DECIMAL : self::BINARY;

		if ( $value < $step ) {
			return $sign . $value . ' ' . $units[0];
		}

		$power = min( (int) floor( log( $value ) / log( $step ) ), count( $units ) - 1 );
		$size  = $value / ( $step ** $power );

		// Bytes are always whole, so a sub-KB value never needs a decimal.
		$rendered = abs( $size - round( $size ) ) < 0.05
			? (string) (int) round( $size )
			: number_format( $size, 1 );

		return $sign . $rendered . ' ' . $units[ $power ];
	}

	/**
	 * Parse a human-written size back to bytes.
	 *
	 * Accepts `2MB`, `2 mb`, `2.5 GiB`, `512K`, `1,024 KB`. Useful for
	 * reading a limit out of a configuration file or a form field, where
	 * requiring a raw byte count is user-hostile.
	 *
	 * Note that `KiB` and `KB` are both read as 1024: the people who
	 * write `KB` in a config file almost always mean the binary unit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Human-written size.
	 *
	 * @return int|null Null when nothing numeric was present.
	 */
	public static function parse( string $value ): ?int {
		$value = str_replace( ',', '', trim( $value ) );

		if ( 1 !== preg_match( '/^(-?\d+(?:\.\d+)?)\s*([a-zA-Z]*)$/', $value, $matches ) ) {
			return null;
		}

		$unit = strtolower( $matches[2] );

		if ( '' === $unit ) {
			return (int) round( (float) $matches[1] );
		}

		if ( ! isset( self::MULTIPLIERS[ $unit ] ) ) {
			return null;
		}

		return (int) round( (float) $matches[1] * self::MULTIPLIERS[ $unit ] );
	}

	/**
	 * Format a transfer rate.
	 *
	 * Decimal units, because network throughput is quoted that way.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $bytes   Bytes transferred.
	 * @param float $seconds Elapsed seconds.
	 *
	 * @return string e.g. `1.4 MB/s`.
	 */
	public static function rate( int $bytes, float $seconds ): string {
		if ( $seconds <= 0.0 ) {
			return '—';
		}

		return self::format( (int) round( $bytes / $seconds ), decimal: true ) . '/s';
	}
}
