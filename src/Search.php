<?php
/**
 * Forgiving search over names.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.2.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format;

/**
 * Class Search
 *
 * Somebody searching a list of their own files remembers roughly what a thing
 * is called, not how they spelled it the day they named it: "license" for
 * "Licence Agreement.pdf", "drive master" for "Midnight Drive - Master.wav".
 * So every word typed has to be found, in any order, and a word of four
 * letters or more may be one typo out: a letter wrong, missing, extra, or
 * swapped with its neighbour.
 *
 * In PHP rather than SQL because LIKE can do the first and not the second,
 * and a list somebody searches by eye is at most a few thousand names. The
 * rules are mirrored exactly by `@arraypress/text`'s `matchesAll()`, so a
 * search in the browser finds what the same search on the server finds.
 *
 * @since 1.2.0
 */
final readonly class Search {

	/**
	 * Shortest word allowed a typo. Below this one wrong letter is a
	 * different word: "mix" and "max", "wav" and "way".
	 */
	public const int FUZZY_FROM = 4;

	/**
	 * The rows whose name matches, in the order given.
	 *
	 * @since 1.2.0
	 *
	 * @param array<int|string, array<string, mixed>> $rows  Rows with a name field.
	 * @param string                                  $query What was typed.
	 * @param string                                  $field Which field holds the name.
	 *
	 * @return array<int, array<string, mixed>> Re-indexed from 0. Every row
	 *                                          when nothing searchable was typed.
	 */
	public static function filter( array $rows, string $query, string $field = 'name' ): array {
		$terms = self::words( $query );

		if ( ! $terms ) {
			return array_values( $rows );
		}

		return array_values( array_filter(
			$rows,
			static fn ( array $row ): bool => self::matches( self::text( $row[ $field ] ?? '' ), $terms )
		) );
	}

	/**
	 * Whether every term is somewhere in a name.
	 *
	 * A term matches when it is inside the name as typed — "strum" in
	 * "Instrumental" — or, from FUZZY_FROM letters, when it is one edit from
	 * a word of the name or from the start of one, so "licens" finds
	 * "licence" before the typing is finished.
	 *
	 * Linear in the lengths of the name and the query: the one-edit test
	 * never builds distance()'s table, so a query pasted in at any length
	 * costs what reading it costs.
	 *
	 * @since 1.2.0
	 *
	 * @param string                    $name  The name.
	 * @param string|array<int, string> $query What was typed, or its words()
	 *                                         already split.
	 *
	 * @return bool True for an empty query.
	 */
	public static function matches( string $name, string|array $query ): bool {
		$terms  = is_string( $query ) ? self::words( $query ) : $query;
		$folded = self::fold( $name );
		$words  = self::words( $name );

		foreach ( $terms as $term ) {
			if ( ! str_contains( $folded, $term ) && ! self::near( $term, $words ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Edit distance by character rather than by byte, so an accented letter
	 * counts as one — and with swapping two neighbouring letters as one edit.
	 *
	 * Optimal string alignment rather than PHP's levenshtein(), because the
	 * commonest typing slip is two letters the wrong way round: "midnigth",
	 * "rian". Levenshtein calls that two edits, which is past the one a
	 * search forgives, so the name plainly meant was not found.
	 *
	 * @since 1.2.0
	 *
	 * @param string $a One word.
	 * @param string $b The other.
	 *
	 * @return int
	 */
	public static function distance( string $a, string $b ): int {
		$s = mb_str_split( $a );
		$t = mb_str_split( $b );
		$m = count( $s );
		$n = count( $t );

		// Rows i-2, i-1 and i of the table: all a transposition looks back at.
		$before   = array();
		$previous = range( 0, $n );

		for ( $i = 1; $i <= $m; $i++ ) {
			$current = array( $i );

			for ( $j = 1; $j <= $n; $j++ ) {
				$cost          = $s[ $i - 1 ] === $t[ $j - 1 ] ? 0 : 1;
				$current[ $j ] = min( $previous[ $j ] + 1, $current[ $j - 1 ] + 1, $previous[ $j - 1 ] + $cost );

				if ( $i > 1 && $j > 1 && $s[ $i - 1 ] === $t[ $j - 2 ] && $s[ $i - 2 ] === $t[ $j - 1 ] ) {
					$current[ $j ] = min( $current[ $j ], $before[ $j - 2 ] + 1 );
				}
			}

			$before   = $previous;
			$previous = $current;
		}

		return $previous[ $n ];
	}

	/**
	 * The words in a name or a query, folded.
	 *
	 * @since 1.2.0
	 *
	 * @param string $text Name or query.
	 *
	 * @return array<int, string> Runs of letters and digits, in any script.
	 */
	public static function words( string $text ): array {
		return array_values( array_filter(
			preg_split( '/[^\p{L}\p{N}]+/u', self::fold( $text ) ) ?: array(),
			static fn ( string $word ): bool => '' !== $word
		) );
	}

	/**
	 * Lower case, and accents off where the intl extension can take them
	 * off — "résumé" found by "resume".
	 *
	 * @since 1.2.0
	 *
	 * @param string $text Name or query.
	 *
	 * @return string
	 */
	public static function fold( string $text ): string {
		$text = mb_strtolower( $text );

		if ( class_exists( \Normalizer::class ) ) {
			$split = (string) \Normalizer::normalize( $text, \Normalizer::FORM_D );
			$text  = preg_replace( '/\p{Mn}+/u', '', $split ) ?? $text;
		}

		return $text;
	}

	/**
	 * Whether a term is one edit from a word, or from the start of one.
	 *
	 * @param string             $term  Folded query word.
	 * @param array<int, string> $words Folded words of the name.
	 *
	 * @return bool
	 */
	private static function near( string $term, array $words ): bool {
		$length = mb_strlen( $term );

		if ( $length < self::FUZZY_FROM ) {
			return false;
		}

		$letters = mb_str_split( $term );

		foreach ( $words as $word ) {
			$whole = mb_str_split( $word );

			if ( self::one_edit( $letters, $whole ) || self::one_edit( $letters, array_slice( $whole, 0, $length ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether two words are at most one edit apart, as distance() counts
	 * edits: equal, or one letter changed, added, dropped, or swapped with
	 * its neighbour.
	 *
	 * The same answer as `distance( $a, $b ) <= 1`, in one pass rather than
	 * a table the size of both words multiplied together.
	 *
	 * @param list<string> $a One word's characters.
	 * @param list<string> $b The other's.
	 *
	 * @return bool
	 */
	private static function one_edit( array $a, array $b ): bool {
		$m = count( $a );
		$n = count( $b );

		if ( abs( $m - $n ) > 1 ) {
			return false;
		}

		// The longer first, so only a deletion from it need be tried.
		if ( $n > $m ) {
			[ $a, $b, $m, $n ] = array( $b, $a, $n, $m );
		}

		$i = 0;

		while ( $i < $n && $a[ $i ] === $b[ $i ] ) {
			++$i;
		}

		if ( $i === $n ) {
			return true;
		}

		if ( $m > $n ) {
			return array_slice( $a, $i + 1 ) === array_slice( $b, $i );
		}

		if ( array_slice( $a, $i + 1 ) === array_slice( $b, $i + 1 ) ) {
			return true;
		}

		return $i + 1 < $n
			&& $a[ $i ] === $b[ $i + 1 ]
			&& $a[ $i + 1 ] === $b[ $i ]
			&& array_slice( $a, $i + 2 ) === array_slice( $b, $i + 2 );
	}

	/**
	 * A row's field as text: strings and numbers as written, anything else
	 * — an array, an object with no string form — as nothing to match.
	 *
	 * @param mixed $value The field.
	 *
	 * @return string
	 */
	private static function text( mixed $value ): string {
		return is_scalar( $value ) || $value instanceof \Stringable ? (string) $value : '';
	}
}
