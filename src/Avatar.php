<?php
/**
 * Deterministic initials avatars.
 *
 * @package   Sugar\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace Sugar\Format;

/**
 * Class Avatar
 *
 * An SVG placeholder built from a name, returned as a `data:` URI ready
 * for an `<img src>`.
 *
 * Deterministic: the same name always yields the same colour, so a list
 * does not reshuffle its palette as you scroll or paginate. The hue comes
 * from a hash of the name; saturation and lightness are fixed, which
 * keeps every generated colour in the same family instead of producing
 * the occasional neon.
 *
 * No network request, no upload, no file to store.
 *
 * @since 1.0.0
 */
final readonly class Avatar {

	/**
	 * Build an avatar.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name       Source text.
	 * @param int    $size       Width and height in pixels.
	 * @param int    $saturation Colour saturation, 0-100.
	 * @param int    $lightness  Colour lightness, 0-100.
	 * @param bool   $from_start Derive initials from the first two words
	 *                           rather than the first and last — right
	 *                           for a product title, wrong for a person.
	 *                           See {@see Text::initials()}.
	 *
	 * @return string A `data:image/svg+xml` URI.
	 */
	public static function svg( string $name, int $size = 200, int $saturation = 55, int $lightness = 42, bool $from_start = false ): string {
		$size       = max( 16, min( 1024, $size ) );
		$saturation = max( 0, min( 100, $saturation ) );
		$lightness  = max( 0, min( 100, $lightness ) );

		$initials = Text::initials( $name, $from_start );
		$hue      = self::hue( $name );

		// The gradient's second stop is offset around the wheel and
		// darkened, which reads as depth without needing a second input.
		$from = sprintf( 'hsl(%d,%d%%,%d%%)', $hue, $saturation, $lightness );
		$to   = sprintf( 'hsl(%d,%d%%,%d%%)', ( $hue + 40 ) % 360, $saturation, max( 0, $lightness - 10 ) );

		// The initials come from user data, so they are XML-escaped. The
		// whole document is then percent-encoded into the data URI, but
		// escaping here means the SVG is well-formed on its own too.
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '"'
			. ' width="' . $size . '" height="' . $size . '">'
			. '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0" stop-color="' . $from . '"/>'
			. '<stop offset="1" stop-color="' . $to . '"/>'
			. '</linearGradient></defs>'
			. '<rect width="' . $size . '" height="' . $size . '" fill="url(#g)"/>'
			. '<text x="50%" y="50%" font-family="ui-sans-serif,system-ui,sans-serif"'
			. ' font-size="' . (int) ( $size * 0.4 ) . '" font-weight="600" fill="#ffffff"'
			. ' text-anchor="middle" dominant-baseline="central">'
			. htmlspecialchars( $initials, ENT_QUOTES | ENT_XML1, 'UTF-8' )
			. '</text></svg>';

		return 'data:image/svg+xml;utf8,' . rawurlencode( $svg );
	}

	/**
	 * The hue a name maps to.
	 *
	 * Exposed so an application can tint something else — a border, a
	 * chart series — to match the avatar beside it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Source text.
	 *
	 * @return int Degrees, 0-359.
	 */
	public static function hue( string $name ): int {
		return (int) ( hexdec( substr( hash( 'sha256', trim( $name ) ), 0, 4 ) ) % 360 );
	}

	/**
	 * The colour a name maps to, as CSS.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name       Source text.
	 * @param int    $saturation Saturation, 0-100.
	 * @param int    $lightness  Lightness, 0-100.
	 *
	 * @return string e.g. `hsl(214,55%,42%)`.
	 */
	public static function colour( string $name, int $saturation = 55, int $lightness = 42 ): string {
		return sprintf( 'hsl(%d,%d%%,%d%%)', self::hue( $name ), $saturation, $lightness );
	}
}
