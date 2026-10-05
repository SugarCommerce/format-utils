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

namespace SugarCommerce\Format\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use SugarCommerce\Format\Search;

#[CoversClass( Search::class )]
final class SearchTest extends TestCase {

	/**
	 * A library somebody searches.
	 */
	private const array LIBRARY = array(
		'Midnight Drive - Instrumental.wav',
		'Licence Agreement.pdf',
		'Midnight Drive - Master.wav',
		'Midnight Drive - Stems.zip',
		'Read Me First.txt',
		'Midnight Drive - Artwork.png',
		'Neon Rain - Master.wav',
		'Résumé.pdf',
	);

	/* ─── Finding what was meant ────────────────────────────────────── */

	/**
	 * @param string             $query What was typed.
	 * @param array<int, string> $found What it should find.
	 */
	#[DataProvider( 'searches' )]
	public function test_a_search_finds_what_was_meant( string $query, array $found ): void {
		$rows = array_map( static fn ( string $name ): array => array( 'name' => $name ), self::LIBRARY );

		$this->assertSame( $found, array_column( Search::filter( $rows, $query ), 'name' ) );
	}

	/**
	 * @return array<string, array{0: string, 1: array<int, string>}>
	 */
	public static function searches(): array {
		return array(
			'the other spelling'       => array( 'License', array( 'Licence Agreement.pdf' ) ),
			'half typed'               => array( 'licens', array( 'Licence Agreement.pdf' ) ),
			'any case'                 => array( 'LICENCE', array( 'Licence Agreement.pdf' ) ),
			'words in any order'       => array( 'master drive', array( 'Midnight Drive - Master.wav' ) ),
			'every word must be there' => array( 'neon drive', array() ),
			'a typo in each word'      => array( 'midnigt mastr', array( 'Midnight Drive - Master.wav' ) ),
			'part of a word'           => array( 'strum', array( 'Midnight Drive - Instrumental.wav' ) ),
			'the extension'            => array( '.zip', array( 'Midnight Drive - Stems.zip' ) ),
			'without the accents'      => array( 'resume', array( 'Résumé.pdf' ) ),
			// Three letters are too few to guess at: "way" is not "wav".
			'no guessing when short'   => array( 'way', array() ),
			'nothing like it'          => array( 'invoice', array() ),
			// Two letters the wrong way round is the commonest slip, and one
			// edit, not the two plain Levenshtein counts it as.
			'two letters swapped'      => array( 'midnigth', array( 'Midnight Drive - Instrumental.wav', 'Midnight Drive - Master.wav', 'Midnight Drive - Stems.zip', 'Midnight Drive - Artwork.png' ) ),
			'swapped in a short word'  => array( 'rian', array( 'Neon Rain - Master.wav' ) ),
			'swapped while typing'     => array( 'licnece', array( 'Licence Agreement.pdf' ) ),
		);
	}

	public function test_the_name_can_be_in_any_field(): void {
		$rows = array( array( 'filename' => 'Master.wav' ), array( 'filename' => 'Stems.zip' ), array( 'other' => 'Master.wav' ) );

		$this->assertSame( array( array( 'filename' => 'Master.wav' ) ), Search::filter( $rows, 'master', 'filename' ) );
	}

	public function test_an_empty_search_is_everything(): void {
		$rows = array( 'a' => array( 'name' => 'a.wav' ), 'b' => array( 'name' => 'b.zip' ) );

		$this->assertSame( array_values( $rows ), Search::filter( $rows, '' ) );
		$this->assertSame( array_values( $rows ), Search::filter( $rows, ' - ' ) );
	}

	public function test_a_name_matches_a_query_or_its_words(): void {
		$this->assertTrue( Search::matches( 'Neon Rain - Master.wav', 'rian mastr' ) );
		$this->assertTrue( Search::matches( 'Neon Rain - Master.wav', array( 'rain' ) ) );
		$this->assertTrue( Search::matches( 'anything', '' ) );
		$this->assertFalse( Search::matches( 'Neon Rain - Master.wav', 'drive' ) );
	}

	/* ─── Distance ──────────────────────────────────────────────────── */

	/**
	 * @param string $a   One word.
	 * @param string $b   The other.
	 * @param int    $was How far apart they are.
	 */
	#[DataProvider( 'distances' )]
	public function test_distance_counts_a_swap_as_one_edit( string $a, string $b, int $was ): void {
		$this->assertSame( $was, Search::distance( $a, $b ) );
		$this->assertSame( $was, Search::distance( $b, $a ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: int}>
	 */
	public static function distances(): array {
		return array(
			'same'                => array( 'rain', 'rain', 0 ),
			'swap'                => array( 'rian', 'rain', 1 ),
			'swap at the end'     => array( 'midnigth', 'midnight', 1 ),
			'substitution'        => array( 'rein', 'rain', 1 ),
			'insertion'           => array( 'rains', 'rain', 1 ),
			'deletion'            => array( 'ran', 'rain', 1 ),
			'empty'               => array( '', 'rain', 4 ),
			// Optimal string alignment edits each part once: "ca" to "abc"
			// is three, where unrestricted Damerau would say two.
			'no edit of a swap'   => array( 'ca', 'abc', 3 ),
			'a letter, not bytes' => array( 'résumé', 'resume', 2 ),
			'two swaps'           => array( 'abdc', 'bacd', 2 ),
		);
	}

	/* ─── Words ─────────────────────────────────────────────────────── */

	public function test_words_are_runs_of_letters_and_digits_in_any_script(): void {
		$this->assertSame( array( 'midnight', 'drive', '2', 'master', 'wav' ), Search::words( 'Midnight Drive 2 — Master.WAV' ) );
		$this->assertSame( array( 'музыка', 'и', 'ноты' ), Search::words( 'Музыка_и_Ноты' ) );
	}

	#[RequiresPhpExtension( 'intl' )]
	public function test_folding_takes_the_case_and_the_accents_off(): void {
		$this->assertSame( 'resume', Search::fold( 'RÉSUMÉ' ) );
	}
}
