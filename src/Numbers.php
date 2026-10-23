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
					? self::whole( $scaled )
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
			? self::whole( $value )
			: number_format( $value, $precision );

		return $rendered . '%';
	}

	/**
	 * A number with a language's own separators: `1.234,5` in German,
	 * `1 234,5` in French, `1,234.5` in English.
	 *
	 * For counts printed on their own, like an allowance of `5.000` files,
	 * where `5,000` in the middle of a German sentence reads as the English
	 * page it came from — and to a German reader as five.
	 *
	 * The same rule as Bytes::format_localized(): English, or no intl
	 * extension, is exactly number_format()'s output, and so is a locale
	 * intl refuses, so one read from a request cannot throw. Halves round
	 * away from zero either way — ICU's default is to the even neighbour,
	 * which would make the two paths disagree about `2.5`.
	 *
	 * @since 1.8.0
	 *
	 * @param int|float $value    The number.
	 * @param string    $locale   A language tag: `de`, `pt-BR`, `fr_FR`.
	 * @param int       $decimals Decimal places, always shown.
	 *
	 * @return string
	 */
	public static function format_localized( int|float $value, string $locale, int $decimals = 0 ): string {
		$decimals = max( 0, $decimals );
		$value    = self::unsigned_zero( $value, $decimals );
		$plain    = number_format( $value, $decimals );

		if ( is_float( $value ) && ! is_finite( $value ) ) {
			return $plain;
		}

		$formatter = self::formatter( $locale, \NumberFormatter::DECIMAL, $decimals, $decimals );
		$written   = $formatter?->format( $value );

		return is_string( $written ) ? $written : $plain;
	}

	/**
	 * A percentage with a language's own separators and sign: `3,9 %` in
	 * German, `3.9%` in English.
	 *
	 * Like percent(), $value is already a percentage — 3.9 for 3.9% — and
	 * English, no intl, or a locale intl refuses is percent()'s output.
	 * The language decides more than the decimal mark: French and German
	 * put a no-break space before the sign, and Arabic marks the direction.
	 *
	 * $trim drops trailing zeros, the way a price list writes a rate:
	 * `10%` and `3.9%` from the same column, not `10.00%` and `3.90%`.
	 *
	 * @since 1.8.0
	 *
	 * @param float  $value     A percentage: 42.4 for 42.4%.
	 * @param string $locale    A language tag: `de`, `pt-BR`, `fr_FR`.
	 * @param int    $precision Decimal places; with $trim, at most this many.
	 * @param bool   $trim      Drop trailing zeros after the decimal mark.
	 *
	 * @return string
	 */
	public static function percent_localized( float $value, string $locale, int $precision = 0, bool $trim = false ): string {
		$precision = max( 0, $precision );
		$value     = (float) self::unsigned_zero( $value, $precision );
		$plain     = self::percent( $value, $precision );

		if ( $trim && $precision > 0 ) {
			$plain = rtrim( rtrim( substr( $plain, 0, -1 ), '0' ), '.' ) . '%';
		}

		if ( ! is_finite( $value ) ) {
			return $plain;
		}

		$formatter = self::formatter( $locale, \NumberFormatter::PERCENT, $trim ? 0 : $precision, $precision );
		$written   = $formatter?->format( $value / 100 );

		return is_string( $written ) ? $written : $plain;
	}

	/**
	 * An intl formatter for a language, or null where the plain English
	 * output stands: English itself, no intl, or a tag intl refuses.
	 *
	 * @param string $locale Language tag.
	 * @param int    $style  NumberFormatter style.
	 * @param int    $min    Fewest decimal places.
	 * @param int    $max    Most decimal places.
	 *
	 * @return \NumberFormatter|null
	 */
	private static function formatter( string $locale, int $style, int $min, int $max ): ?\NumberFormatter {
		if ( '' === $locale || str_starts_with( strtolower( $locale ), 'en' ) || ! class_exists( \NumberFormatter::class ) ) {
			return null;
		}

		try {
			$formatter = new \NumberFormatter( str_replace( '-', '_', $locale ), $style );
		} catch ( \ValueError | \IntlException ) {
			return null;
		}

		$formatter->setAttribute( \NumberFormatter::MIN_FRACTION_DIGITS, $min );
		$formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $max );
		$formatter->setAttribute( \NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP );

		return $formatter;
	}

	/**
	 * Zero for a value that rounds to it, so nothing prints `-0`.
	 *
	 * number_format() already drops the sign; ICU keeps it, and `-0 %` beside
	 * a rate that did not change reads as a fall.
	 *
	 * @param int|float $value    Value.
	 * @param int       $decimals Decimal places it will be shown to.
	 *
	 * @return int|float
	 */
	private static function unsigned_zero( int|float $value, int $decimals ): int|float {
		return is_float( $value ) && 0.0 === round( $value, $decimals ) ? 0.0 : $value;
	}

	/**
	 * A float rounded and written with no decimal point or separators.
	 *
	 * Through an int where one holds it — so `-0.4` is `0`, not `-0` — and
	 * written out as a float where none does, rather than cast and wrapped
	 * into some other number.
	 *
	 * @param float $value Value.
	 *
	 * @return string
	 */
	private static function whole( float $value ): string {
		$rounded = round( $value );

		if ( $rounded >= (float) PHP_INT_MIN && $rounded < (float) PHP_INT_MAX ) {
			return (string) (int) $rounded;
		}

		return is_finite( $rounded ) ? number_format( $rounded, 0, '.', '' ) : (string) $rounded;
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
