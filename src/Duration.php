<?php
/**
 * Elapsed time.
 *
 * @package   SugarMill\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarMill\Format;

/**
 * Class Duration
 *
 * A length of time, in three registers.
 *
 * `clock()` for media, where alignment matters and the reader is
 * scrubbing. `compact()` for tables. `words()` for prose, where "about
 * two hours" is more useful than "2h 3m".
 *
 * @since 1.0.0
 */
final readonly class Duration {

	/**
	 * A media timestamp: `4:03`, `1:02:03`.
	 *
	 * The hour component is omitted below an hour, because `0:04:03` on a
	 * four-minute track is noise.
	 *
	 * @since 1.0.0
	 *
	 * @param int $seconds Duration in seconds.
	 *
	 * @return string
	 */
	public static function clock( int $seconds ): string {
		$sign    = $seconds < 0 ? '-' : '';
		$seconds = abs( $seconds );

		$hours   = intdiv( $seconds, 3600 );
		$minutes = intdiv( $seconds % 3600, 60 );
		$rest    = $seconds % 60;

		if ( $hours > 0 ) {
			return sprintf( '%s%d:%02d:%02d', $sign, $hours, $minutes, $rest );
		}

		return sprintf( '%s%d:%02d', $sign, $minutes, $rest );
	}

	/**
	 * A compact breakdown: `2h 5m`, `45s`, `3d 4h`.
	 *
	 * Shows at most two units, because the third never changes a decision.
	 *
	 * @since 1.0.0
	 *
	 * @param int $seconds Duration in seconds.
	 * @param int $parts   How many units to show.
	 *
	 * @return string
	 */
	public static function compact( int $seconds, int $parts = 2 ): string {
		$sign    = $seconds < 0 ? '-' : '';
		$seconds = abs( $seconds );

		if ( 0 === $seconds ) {
			return '0s';
		}

		$units = array( 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1 );
		$out   = array();

		foreach ( $units as $suffix => $length ) {
			if ( count( $out ) >= max( 1, $parts ) ) {
				break;
			}

			$count = intdiv( $seconds, $length );

			if ( $count > 0 || array() !== $out ) {
				if ( $count > 0 ) {
					$out[] = $count . $suffix;
				}

				$seconds %= $length;
			}
		}

		return $sign . implode( ' ', $out );
	}

	/**
	 * A rounded phrase: `about 2 hours`, `just under a minute`.
	 *
	 * Deliberately imprecise. Where a duration is being read rather than
	 * measured, a rounded phrase carries the meaning and a precise one
	 * invites the reader to do arithmetic they did not want to do.
	 *
	 * @since 1.0.0
	 *
	 * @param int $seconds Duration in seconds.
	 *
	 * @return string
	 */
	public static function words( int $seconds ): string {
		$seconds = abs( $seconds );

		return match ( true ) {
			$seconds < 5      => 'a moment',
			$seconds < 45     => $seconds . ' seconds',
			$seconds < 90     => 'about a minute',
			$seconds < 3600   => 'about ' . (int) round( $seconds / 60 ) . ' minutes',
			$seconds < 5400   => 'about an hour',
			$seconds < 86400  => 'about ' . (int) round( $seconds / 3600 ) . ' hours',
			$seconds < 172800 => 'about a day',
			default           => 'about ' . (int) round( $seconds / 86400 ) . ' days',
		};
	}

	/**
	 * Parse `1:02:03`, `4:03`, `90s`, `2h 5m` back to seconds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Written duration.
	 *
	 * @return int|null Null when unparseable.
	 */
	public static function parse( string $value ): ?int {
		$value = trim( strtolower( $value ) );

		if ( '' === $value ) {
			return null;
		}

		// Clock form.
		if ( 1 === preg_match( '/^(\d+):([0-5]?\d)(?::([0-5]?\d))?$/', $value, $m ) ) {
			return isset( $m[3] ) && '' !== $m[3]
				? ( (int) $m[1] * 3600 ) + ( (int) $m[2] * 60 ) + (int) $m[3]
				: ( (int) $m[1] * 60 ) + (int) $m[2];
		}

		// Suffixed form, possibly several parts.
		if ( 0 === preg_match_all( '/(\d+(?:\.\d+)?)\s*(d|h|m|s)/', $value, $matches, PREG_SET_ORDER ) ) {
			return null;
		}

		$units   = array( 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1 );
		$seconds = 0.0;

		foreach ( $matches as $match ) {
			$seconds += (float) $match[1] * $units[ $match[2] ];
		}

		return (int) round( $seconds );
	}
}
