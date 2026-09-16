<?php
/**
 * Numbers for display.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format;

/**
 * Class Numbers
 *
 * For money, use `sugarcommerce/currency` — money is integer minor units and
 * needs its own rules. This is for counts and rates.
 *
 * @since 1.0.0
 */
final readonly class Numbers {

	/**
	 * Abbreviate a large count: `1.2k`, `3.4M`, `1.1B`.
	 *
	 * Below the threshold the number is left alone, because `847` is
	 * already readable and `0.8k` is worse.
	 *
	 * @since 1.0.0
	 *
	 * @param int|float $value     Value.
	 * @param int       $precision Decimal places on an abbreviated value.
	 *
	 * @return string
	 */
	public static function compact( int|float $value, int $precision = 1 ): string {
		$sign     = $value < 0 ? '-' : '';
		$absolute = abs( $value );

		if ( $absolute < 1000 ) {
			return $sign . (string) ( is_float( $value ) ? round( $absolute, $precision ) : (int) $absolute );
		}

		foreach ( array( 'T' => 1e12, 'B' => 1e9, 'M' => 1e6, 'k' => 1e3 ) as $suffix => $threshold ) {
			if ( $absolute >= $threshold ) {
				$scaled = $absolute / $threshold;

				// A whole value drops its decimal: "2k", not "2.0k".
				$rendered = abs( $scaled - round( $scaled ) ) < 0.05
					? (string) (int) round( $scaled )
					: number_format( $scaled, $precision );

				return $sign . $rendered . $suffix;
			}
		}

		return $sign . (string) $absolute;
	}

	/**
	 * A percentage.
	 *
	 * @since 1.0.0
	 *
	 * @param float $value     Ratio, or a percentage when `$of` is given.
	 * @param int   $precision Decimal places.
	 *
	 * @return string
	 */
	public static function percent( float $value, int $precision = 0 ): string {
		$rendered = 0 === $precision
			? (string) (int) round( $value )
			: number_format( $value, $precision );

		return $rendered . '%';
	}

	/**
	 * One number as a percentage of another.
	 *
	 * @since 1.0.0
	 *
	 * @param float $part      The part.
	 * @param float $whole     The whole.
	 * @param int   $precision Decimal places.
	 *
	 * @return string `—` when the whole is zero, since a share of nothing
	 *                is undefined rather than 0%.
	 */
	public static function share( float $part, float $whole, int $precision = 0 ): string {
		if ( 0.0 === $whole ) {
			return '—';
		}

		return self::percent( $part / $whole * 100, $precision );
	}

	/**
	 * A signed change, for a metric next to its previous period.
	 *
	 * @since 1.0.0
	 *
	 * @param float $current   Current value.
	 * @param float $previous  Previous value.
	 * @param int   $precision Decimal places.
	 *
	 * @return string e.g. `+12%`, `-4%`, `new` when there was nothing before.
	 */
	public static function change( float $current, float $previous, int $precision = 0 ): string {
		if ( 0.0 === $previous ) {
			return 0.0 === $current ? '—' : 'new';
		}

		$delta = ( $current - $previous ) / abs( $previous ) * 100;

		return ( $delta >= 0 ? '+' : '' ) . self::percent( $delta, $precision );
	}
}
