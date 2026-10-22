<?php
/**
 * Text for display.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format;

/**
 * Class Text
 *
 * Everything here returns plain text and escapes nothing. Escape at the
 * point of output, for the encoding of the place it is going — these
 * results are as safe to escape as their inputs were, and no safer.
 *
 * @since 1.0.0
 */
final readonly class Text {

	/**
	 * Shorten to a length without cutting a word in half.
	 *
	 * Counts characters, not bytes, so a limit of 20 means twenty visible
	 * characters whether the text is English or Japanese. The ellipsis is
	 * included in the budget, because a "20 character" label that renders
	 * 21 breaks a table column.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text     Source text.
	 * @param int    $length   Maximum length, including the ellipsis.
	 * @param string $ellipsis Appended when shortened.
	 *
	 * @return string
	 */
	public static function truncate( string $text, int $length = 100, string $ellipsis = '…' ): string {
		$text = trim( $text );

		if ( $length <= 0 || mb_strlen( $text ) <= $length ) {
			return $text;
		}

		$budget = max( 1, $length - mb_strlen( $ellipsis ) );
		$cut    = mb_substr( $text, 0, $budget );
		$space  = mb_strrpos( $cut, ' ' );

		// Only break at a word boundary if one is reasonably near the end,
		// otherwise a long unbroken string would collapse to almost nothing.
		if ( false !== $space && $space > (int) ( $budget * 0.6 ) ) {
			$cut = mb_substr( $cut, 0, $space );
		}

		return rtrim( $cut, " \t\n\r\0\x0B.,;:!?-" ) . $ellipsis;
	}

	/**
	 * Keep both ends, drop the middle: `a1b2c3d4e5…f6a7b8c9`.
	 *
	 * For identifiers people compare by eye — hashes, keys, long references —
	 * where the start says what it is and the end tells two apart, so
	 * truncate() keeping only the start would make different values look
	 * the same. Counts characters, not bytes. Returned unchanged when
	 * shortening would not make it shorter.
	 *
	 * For a credential, where the point is that it cannot be read back, use
	 * sanitize's Input::mask() instead: this shows as much as it is told to.
	 *
	 * @since 1.6.0
	 *
	 * @param string $text     Source text.
	 * @param int    $head     Characters kept from the start.
	 * @param int    $tail     Characters kept from the end.
	 * @param string $ellipsis Put where the middle was.
	 *
	 * @return string
	 */
	public static function middle( string $text, int $head, int $tail, string $ellipsis = '…' ): string {
		$head = max( 0, $head );
		$tail = max( 0, $tail );

		if ( mb_strlen( $text ) <= $head + $tail + mb_strlen( $ellipsis ) ) {
			return $text;
		}

		return mb_substr( $text, 0, $head ) . $ellipsis . ( $tail > 0 ? mb_substr( $text, -$tail ) : '' );
	}

	/**
	 * Collapse to a single-line summary.
	 *
	 * Whitespace runs and line breaks become single spaces before
	 * truncating, so a multi-paragraph body becomes one clean line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text   Source text.
	 * @param int    $length Maximum length.
	 *
	 * @return string
	 */
	public static function excerpt( string $text, int $length = 160 ): string {
		$text = (string) preg_replace( '/\s+/u', ' ', $text );

		return self::truncate( $text, $length );
	}

	/**
	 * Plain text as its paragraphs.
	 *
	 * A line break in text somebody typed into a single field — a product
	 * description, a note — is them starting a new paragraph, so every
	 * break is one: blank lines in between do not add empty paragraphs,
	 * and each paragraph is trimmed. Nothing is escaped or interpreted,
	 * so what comes back is still plain text, to be escaped where it is
	 * printed.
	 *
	 * @since 1.3.0
	 *
	 * @param string $text Source text, with any line endings.
	 *
	 * @return list<string> The paragraphs, in order. Empty for blank text.
	 */
	public static function paragraphs( string $text ): array {
		$lines = preg_split( '/\R/u', $text ) ?: array();

		return array_values( array_filter(
			array_map( static fn( string $line ): string => trim( (string) preg_replace( '/[ \t]+/u', ' ', $line ) ), $lines ),
			static fn( string $line ): bool => '' !== $line
		) );
	}

	/**
	 * A machine key as words a person reads: `sample_rate` → `Sample rate`.
	 *
	 * For keys somebody chose to name a value — metadata, a settings
	 * array, a column — shown as that value's label. Underscores, hyphens
	 * and dots become spaces, camelCase is split, and only the first
	 * letter is capitalised: sentence case, because `Bit Depth` reads as
	 * a title and `Bit depth` reads as a label. A word already written in
	 * capitals (`BPM`, `ISRC`) is kept as written.
	 *
	 * @since 1.3.0
	 *
	 * @param string $key Source key.
	 *
	 * @return string '' for a key with no letters or digits in it.
	 */
	public static function humanize( string $key ): string {
		$spaced = (string) preg_replace( '/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $key );
		$words  = preg_split( '/[\s_.\-]+/u', trim( $spaced ), -1, PREG_SPLIT_NO_EMPTY ) ?: array();

		$words = array_map(
			static fn( string $word ): string => mb_strlen( $word ) > 1 && mb_strtoupper( $word ) === $word ? $word : mb_strtolower( $word ),
			$words
		);

		if ( array() === $words ) {
			return '';
		}

		$words[0] = mb_strtoupper( mb_substr( $words[0], 0, 1 ) ) . mb_substr( $words[0], 1 );

		return implode( ' ', $words );
	}

	/**
	 * A name in consistent case: `GEORGE HILL` → `George Hill`.
	 *
	 * For names typed by their owners and imported from wherever they were
	 * typed — all capitals, all lower case, a mix of both — so a list of
	 * them reads as one list. Lower-cased first, so `McDonald` becomes
	 * `Mcdonald` and `o'brien` becomes `O'brien`: nothing here knows which
	 * capitals were deliberate, and a list where some were kept and some
	 * were not looks worse than one rule applied evenly.
	 *
	 * Words split on whitespace only, and the first letter of each is
	 * capitalised the way ucwords() does it, as a byte: a word starting
	 * with a letter outside ASCII — `élodie` — keeps it in lower case
	 * rather than guessing at a locale. The rest of the name is lowered as
	 * UTF-8, so `ÉLODIE` is `élodie`.
	 *
	 * @since 1.5.0
	 *
	 * @param string $name Source name.
	 *
	 * @return string Trimmed; '' when there was nothing but whitespace.
	 */
	public static function name_case( string $name ): string {
		return ucwords( mb_strtolower( trim( $name ), 'UTF-8' ) );
	}

	/**
	 * Up to two initials.
	 *
	 * Which two depends on what the text is, and the two cases disagree:
	 *
	 *   "Dave Michael Sherlock"   first and last -> DS   (a person)
	 *   "Trance Essentials Vol 1" first two     -> TE   (a title)
	 *
	 * First-and-last is right for a name, where the surname identifies
	 * the person. First-two is right for a title, where the trailing word
	 * is usually a volume or edition number — first-and-last would render
	 * that example as `T1`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text       Source text.
	 * @param bool   $from_start Take the first two words rather than the
	 *                           first and last. Pass it by name:
	 *                           `Text::initials( $title, from_start: true )`.
	 *
	 * @return string Uppercase; `?` when there is nothing usable.
	 */
	public static function initials( string $text, bool $from_start = false ): string {
		$parts = preg_split( '/[\s\-_]+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) ?: array();

		if ( array() === $parts ) {
			return '?';
		}

		$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) );

		if ( count( $parts ) > 1 ) {
			$second    = $from_start ? $parts[1] : $parts[ count( $parts ) - 1 ];
			$initials .= mb_strtoupper( mb_substr( $second, 0, 1 ) );
		}

		return $initials;
	}

	/**
	 * Join a list the way a person writes one.
	 *
	 * `['a']` → `a`; `['a','b']` → `a and b`; `['a','b','c']` → `a, b and c`.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $items       Items. Anything but a non-blank string
	 *                                  is left out.
	 * @param string       $conjunction Word before the last item.
	 * @param int          $limit       Show at most this many, then "and N
	 *                                  more".
	 *
	 * @return string
	 */
	public static function list( array $items, string $conjunction = 'and', int $limit = 0 ): string {
		$items = array_values( array_filter( $items, static fn( $i ): bool => is_string( $i ) && '' !== trim( $i ) ) );
		$count = count( $items );

		if ( 0 === $count ) {
			return '';
		}

		if ( $limit > 0 && $count > $limit ) {
			$shown     = array_slice( $items, 0, $limit );
			$remaining = $count - $limit;

			return implode( ', ', $shown ) . ' ' . $conjunction . ' ' . $remaining . ' more';
		}

		if ( 1 === $count ) {
			return $items[0];
		}

		$last = array_pop( $items );

		return implode( ', ', $items ) . ' ' . $conjunction . ' ' . $last;
	}

	/**
	 * Pluralise a noun against a count.
	 *
	 * Handles the common English endings; pass `$plural` for anything
	 * irregular. Not a linguistics engine — "person" will become
	 * "persons" unless you say otherwise.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $count    How many.
	 * @param string $singular Singular noun.
	 * @param string $plural   Explicit plural.
	 *
	 * @return string e.g. `3 files`, `1 entry`.
	 */
	public static function plural( int $count, string $singular, string $plural = '' ): string {
		if ( 1 === abs( $count ) ) {
			return $count . ' ' . $singular;
		}

		if ( '' !== $plural ) {
			return $count . ' ' . $plural;
		}

		$formed = match ( true ) {
			// "entry" → "entries", but "day" → "days".
			1 === preg_match( '/[^aeiou]y$/i', $singular ) => mb_substr( $singular, 0, -1 ) . 'ies',
			1 === preg_match( '/(s|x|z|ch|sh)$/i', $singular ) => $singular . 'es',
			default => $singular . 's',
		};

		return $count . ' ' . $formed;
	}

	/**
	 * An ordinal: 1st, 2nd, 3rd, 11th.
	 *
	 * @since 1.0.0
	 *
	 * @param int $number Number.
	 *
	 * @return string
	 */
	public static function ordinal( int $number ): string {
		$absolute = abs( $number );

		// 11, 12 and 13 take "th" despite ending in 1, 2 and 3.
		if ( in_array( $absolute % 100, array( 11, 12, 13 ), true ) ) {
			return $number . 'th';
		}

		return $number . match ( $absolute % 10 ) {
			1       => 'st',
			2       => 'nd',
			3       => 'rd',
			default => 'th',
		};
	}
}
