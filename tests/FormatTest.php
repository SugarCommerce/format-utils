<?php
/**
 * Display formatting.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SugarCommerce\Format\Avatar;
use SugarCommerce\Format\Bytes;
use SugarCommerce\Format\Duration;
use SugarCommerce\Format\FileType;
use SugarCommerce\Format\Numbers;
use SugarCommerce\Format\Text;

#[CoversClass( Bytes::class )]
#[CoversClass( Duration::class )]
#[CoversClass( Text::class )]
#[CoversClass( Numbers::class )]
#[CoversClass( Avatar::class )]
#[CoversClass( FileType::class )]
final class FormatTest extends TestCase {

	/* ─── Bytes ─────────────────────────────────────────────────────── */

	#[DataProvider( 'byte_sizes' )]
	public function test_bytes_format( int $bytes, string $expected ): void {
		$this->assertSame( $expected, Bytes::format( $bytes ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string}>
	 */
	public static function byte_sizes(): array {
		return array(
			'zero'          => array( 0, '0 B' ),
			'bytes'         => array( 42, '42 B' ),
			'just under KB' => array( 1023, '1023 B' ),
			'one KB'        => array( 1024, '1 KB' ),
			'kilobytes'     => array( 2560, '2.5 KB' ),
			'clean KB'      => array( 10240, '10 KB' ),
			'one MB'        => array( 1048576, '1 MB' ),
			// The reason for the 5% rounding window: nobody wants "100.0 MB".
			'clean MB'      => array( 104857600, '100 MB' ),
			'ragged MB'     => array( 1572864, '1.5 MB' ),
			'one GB'        => array( 1073741824, '1 GB' ),
			'ragged GB'     => array( 1610612736, '1.5 GB' ),
			'terabytes'     => array( 1099511627776, '1 TB' ),
			'negative'      => array( -2048, '-2 KB' ),
		);
	}

	public function test_decimal_units_differ_from_binary(): void {
		$this->assertSame( '1000 B', Bytes::format( 1000 ) );
		$this->assertSame( '1 kB', Bytes::format( 1000, decimal: true ) );
		$this->assertSame( '1 KB', Bytes::format( 1024 ) );
	}

	/**
	 * The "why is my 500 GB drive 465 GB?" gap, in one assertion.
	 */
	public function test_the_two_conventions_disagree_as_expected(): void {
		$fiveHundredGb = 500 * ( 1000 ** 3 );

		$this->assertSame( '500 GB', Bytes::format( $fiveHundredGb, decimal: true ) );
		$this->assertSame( '465.7 GB', Bytes::format( $fiveHundredGb ) );
	}

	#[DataProvider( 'parsable_sizes' )]
	public function test_bytes_parse( string $input, ?int $expected ): void {
		$this->assertSame( $expected, Bytes::parse( $input ) );
	}

	/**
	 * @return array<string, array{0: string, 1: int|null}>
	 */
	public static function parsable_sizes(): array {
		return array(
			'plain'        => array( '1024', 1024 ),
			'no space'     => array( '2MB', 2097152 ),
			'spaced'       => array( '2 MB', 2097152 ),
			'lowercase'    => array( '2 mb', 2097152 ),
			'binary form'  => array( '2 MiB', 2097152 ),
			'single letter' => array( '512K', 524288 ),
			'fractional'   => array( '1.5 GB', 1610612736 ),
			'thousands'    => array( '1,024 KB', 1048576 ),
			'nonsense'     => array( 'big', null ),
			'bad unit'     => array( '5 QB', null ),
			'empty'        => array( '', null ),
		);
	}

	public function test_sizes_survive_a_round_trip(): void {
		foreach ( array( 1024, 2097152, 1610612736 ) as $bytes ) {
			$this->assertSame( $bytes, Bytes::parse( Bytes::format( $bytes ) ) );
		}
	}

	public function test_transfer_rate(): void {
		$this->assertSame( '1 MB/s', Bytes::rate( 1000000, 1.0 ) );
		$this->assertSame( '500 kB/s', Bytes::rate( 1000000, 2.0 ) );
		$this->assertSame( '—', Bytes::rate( 100, 0.0 ) );
	}

	/* ─── Duration ──────────────────────────────────────────────────── */

	#[DataProvider( 'clock_times' )]
	public function test_duration_clock( int $seconds, string $expected ): void {
		$this->assertSame( $expected, Duration::clock( $seconds ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string}>
	 */
	public static function clock_times(): array {
		return array(
			'zero'        => array( 0, '0:00' ),
			'seconds'     => array( 43, '0:43' ),
			'minutes'     => array( 243, '4:03' ),
			'ten minutes' => array( 600, '10:00' ),
			// Below an hour the hour component is noise on a track listing.
			'hour'        => array( 3723, '1:02:03' ),
			'long'        => array( 36000, '10:00:00' ),
			'negative'    => array( -90, '-1:30' ),
		);
	}

	#[DataProvider( 'compact_durations' )]
	public function test_duration_compact( int $seconds, string $expected ): void {
		$this->assertSame( $expected, Duration::compact( $seconds ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string}>
	 */
	public static function compact_durations(): array {
		return array(
			'zero'      => array( 0, '0s' ),
			'seconds'   => array( 45, '45s' ),
			'minutes'   => array( 90, '1m 30s' ),
			'hours'     => array( 7500, '2h 5m' ),
			'days'      => array( 273600, '3d 4h' ),
			'exact hour' => array( 3600, '1h' ),
		);
	}

	public function test_compact_can_show_one_unit(): void {
		$this->assertSame( '2h', Duration::compact( 7500, parts: 1 ) );
	}

	#[DataProvider( 'spoken_durations' )]
	public function test_duration_words( int $seconds, string $expected ): void {
		$this->assertSame( $expected, Duration::words( $seconds ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string}>
	 */
	public static function spoken_durations(): array {
		return array(
			'instant' => array( 2, 'a moment' ),
			'seconds' => array( 30, '30 seconds' ),
			'minute'  => array( 60, 'about a minute' ),
			'minutes' => array( 600, 'about 10 minutes' ),
			'hour'    => array( 3600, 'about an hour' ),
			'hours'   => array( 18000, 'about 5 hours' ),
			'day'     => array( 90000, 'about a day' ),
			'days'    => array( 604800, 'about 7 days' ),
		);
	}

	#[DataProvider( 'parsable_durations' )]
	public function test_duration_parse( string $input, ?int $expected ): void {
		$this->assertSame( $expected, Duration::parse( $input ) );
	}

	/**
	 * @return array<string, array{0: string, 1: int|null}>
	 */
	public static function parsable_durations(): array {
		return array(
			'clock mm:ss'   => array( '4:03', 243 ),
			'clock h:mm:ss' => array( '1:02:03', 3723 ),
			'seconds'       => array( '90s', 90 ),
			'minutes'       => array( '5m', 300 ),
			'combined'      => array( '2h 5m', 7500 ),
			'full'          => array( '1d 2h 3m 4s', 93784 ),
			'nonsense'      => array( 'ages', null ),
			'empty'         => array( '', null ),
		);
	}

	public function test_durations_survive_a_round_trip(): void {
		foreach ( array( 243, 3723, 90 ) as $seconds ) {
			$this->assertSame( $seconds, Duration::parse( Duration::clock( $seconds ) ) );
		}
	}

	/* ─── Text ──────────────────────────────────────────────────────── */

	public function test_short_text_is_left_alone(): void {
		$this->assertSame( 'Short', Text::truncate( 'Short', 20 ) );
	}

	/**
	 * A "20 character" label that renders 21 breaks a table column.
	 */
	public function test_truncation_includes_the_ellipsis_in_the_budget(): void {
		$result = Text::truncate( 'The quick brown fox jumps over the lazy dog', 20 );

		$this->assertLessThanOrEqual( 20, mb_strlen( $result ) );
		$this->assertStringEndsWith( '…', $result );
	}

	public function test_truncation_does_not_split_a_word(): void {
		$this->assertSame( 'The quick brown…', Text::truncate( 'The quick brown fox jumps', 18 ) );
	}

	/**
	 * Word-boundary breaking must not collapse an unbroken string to
	 * almost nothing.
	 */
	public function test_an_unbroken_string_is_cut_where_needed(): void {
		$result = Text::truncate( 'a ' . str_repeat( 'x', 40 ), 20 );

		$this->assertGreaterThan( 10, mb_strlen( $result ) );
	}

	public function test_truncation_counts_characters_not_bytes(): void {
		$this->assertSame( '日本語のタイトル', Text::truncate( '日本語のタイトル', 20 ) );
	}

	public function test_excerpt_collapses_whitespace(): void {
		$this->assertSame( 'One two three', Text::excerpt( "One\n\n  two\t three", 100 ) );
	}

	#[DataProvider( 'initial_cases' )]
	public function test_initials( string $input, string $expected ): void {
		$this->assertSame( $expected, Text::initials( $input ) );
	}

	/**
	 * The two cases genuinely disagree: a surname identifies a person, but
	 * the trailing word of a title is usually a volume number.
	 */
	public function test_titles_take_their_initials_from_the_start(): void {
		$this->assertSame( 'T1', Text::initials( 'Trance Essentials Vol 1' ) );
		$this->assertSame( 'TE', Text::initials( 'Trance Essentials Vol 1', from_start: true ) );

		$this->assertSame( 'DS', Text::initials( 'Dave Michael Sherlock' ) );
		$this->assertSame( 'DM', Text::initials( 'Dave Michael Sherlock', from_start: true ) );
	}

	public function test_a_single_word_is_unaffected_by_the_mode(): void {
		$this->assertSame( 'D', Text::initials( 'Dave' ) );
		$this->assertSame( 'D', Text::initials( 'Dave', from_start: true ) );
	}

	public function test_an_avatar_carries_the_initials_mode(): void {
		$title = 'Trance Essentials Vol 1';

		$this->assertStringContainsString( 'T1', rawurldecode( Avatar::svg( $title ) ) );
		$this->assertStringContainsString( 'TE', rawurldecode( Avatar::svg( $title, from_start: true ) ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function initial_cases(): array {
		return array(
			'two words'  => array( 'Dave Sherlock', 'DS' ),
			'one word'   => array( 'Dave', 'D' ),
			'three words' => array( 'Trance Essentials Vol', 'TV' ), // first and last
			'hyphenated' => array( 'Jean-Luc', 'JL' ),
			'lowercase'  => array( 'dave sherlock', 'DS' ),
			'empty'      => array( '', '?' ),
			'whitespace' => array( '   ', '?' ),
		);
	}

	#[DataProvider( 'list_cases' )]
	public function test_list( array $items, string $expected ): void {
		$this->assertSame( $expected, Text::list( $items ) );
	}

	/**
	 * @return array<string, array{0: string[], 1: string}>
	 */
	public static function list_cases(): array {
		return array(
			'empty' => array( array(), '' ),
			'one'   => array( array( 'a' ), 'a' ),
			'two'   => array( array( 'a', 'b' ), 'a and b' ),
			'three' => array( array( 'a', 'b', 'c' ), 'a, b and c' ),
			'blanks dropped' => array( array( 'a', '', 'b' ), 'a and b' ),
		);
	}

	public function test_a_long_list_is_capped(): void {
		$this->assertSame( 'a, b and 2 more', Text::list( array( 'a', 'b', 'c', 'd' ), limit: 2 ) );
	}

	#[DataProvider( 'plural_cases' )]
	public function test_plural( int $count, string $singular, string $expected ): void {
		$this->assertSame( $expected, Text::plural( $count, $singular ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string, 2: string}>
	 */
	public static function plural_cases(): array {
		return array(
			'one'        => array( 1, 'file', '1 file' ),
			'many'       => array( 3, 'file', '3 files' ),
			'zero'       => array( 0, 'file', '0 files' ),
			'consonant y' => array( 2, 'entry', '2 entries' ),
			'vowel y'    => array( 2, 'day', '2 days' ),
			'sibilant'   => array( 2, 'box', '2 boxes' ),
			'ch'         => array( 2, 'batch', '2 batches' ),
		);
	}

	public function test_an_irregular_plural_can_be_given(): void {
		$this->assertSame( '2 people', Text::plural( 2, 'person', 'people' ) );
	}

	#[DataProvider( 'ordinal_cases' )]
	public function test_ordinal( int $number, string $expected ): void {
		$this->assertSame( $expected, Text::ordinal( $number ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string}>
	 */
	public static function ordinal_cases(): array {
		return array(
			'first'   => array( 1, '1st' ),
			'second'  => array( 2, '2nd' ),
			'third'   => array( 3, '3rd' ),
			'fourth'  => array( 4, '4th' ),
			// The teens are the trap: 11th, not 11st.
			'eleventh' => array( 11, '11th' ),
			'twelfth' => array( 12, '12th' ),
			'thirteenth' => array( 13, '13th' ),
			'twenty-first' => array( 21, '21st' ),
			'hundred and eleventh' => array( 111, '111th' ),
			'hundred and twenty-first' => array( 121, '121st' ),
		);
	}

	/* ─── Numbers ───────────────────────────────────────────────────── */

	#[DataProvider( 'compact_numbers' )]
	public function test_compact_numbers( int|float $value, string $expected ): void {
		$this->assertSame( $expected, Numbers::compact( $value ) );
	}

	/**
	 * @return array<string, array{0: int|float, 1: string}>
	 */
	public static function compact_numbers(): array {
		return array(
			'small'    => array( 847, '847' ),
			'exact k'  => array( 1000, '1k' ),
			'ragged k' => array( 1200, '1.2k' ),
			'million'  => array( 3400000, '3.4M' ),
			'billion'  => array( 1100000000, '1.1B' ),
			'negative' => array( -1200, '-1.2k' ),
			'zero'     => array( 0, '0' ),
		);
	}

	public function test_percent(): void {
		$this->assertSame( '42%', Numbers::percent( 42.4 ) );
		$this->assertSame( '42.4%', Numbers::percent( 42.4, 1 ) );
	}

	public function test_share(): void {
		$this->assertSame( '25%', Numbers::share( 25, 100 ) );
		$this->assertSame( '—', Numbers::share( 5, 0 ), 'A share of nothing is undefined, not zero.' );
	}

	public function test_change(): void {
		$this->assertSame( '+20%', Numbers::change( 120, 100 ) );
		$this->assertSame( '-20%', Numbers::change( 80, 100 ) );
		$this->assertSame( 'new', Numbers::change( 50, 0 ) );
		$this->assertSame( '—', Numbers::change( 0, 0 ) );
	}

	/* ─── Avatar ────────────────────────────────────────────────────── */

	public function test_an_avatar_is_a_data_uri(): void {
		$this->assertStringStartsWith( 'data:image/svg+xml;utf8,', Avatar::svg( 'Dave Sherlock' ) );
	}

	/**
	 * A list must not reshuffle its palette as you scroll.
	 */
	public function test_the_same_name_always_gives_the_same_colour(): void {
		$this->assertSame( Avatar::hue( 'Dave' ), Avatar::hue( 'Dave' ) );
		$this->assertSame( Avatar::svg( 'Dave' ), Avatar::svg( 'Dave' ) );
		$this->assertNotSame( Avatar::hue( 'Dave' ), Avatar::hue( 'Sarah' ) );
	}

	public function test_hues_stay_in_range(): void {
		foreach ( array( 'a', 'Dave', '日本語', '', str_repeat( 'x', 500 ) ) as $name ) {
			$hue = Avatar::hue( $name );

			$this->assertGreaterThanOrEqual( 0, $hue );
			$this->assertLessThan( 360, $hue );
		}
	}

	public function test_an_avatar_contains_the_initials(): void {
		$this->assertStringContainsString( 'DS', rawurldecode( Avatar::svg( 'Dave Sherlock' ) ) );
	}

	/**
	 * The name is user data being written into markup.
	 */
	public function test_initials_are_escaped_in_the_svg(): void {
		$svg = rawurldecode( Avatar::svg( '<script>alert(1)</script> Boom' ) );

		$this->assertStringNotContainsString( '<script>', $svg );
	}

	public function test_avatar_size_is_bounded(): void {
		$this->assertStringContainsString( 'width="16"', rawurldecode( Avatar::svg( 'x', 1 ) ) );
		$this->assertStringContainsString( 'width="1024"', rawurldecode( Avatar::svg( 'x', 99999 ) ) );
	}

	public function test_colour_is_css(): void {
		$this->assertMatchesRegularExpression( '/^hsl\(\d{1,3},55%,42%\)$/', Avatar::colour( 'Dave' ) );
	}

	/* ─── FileType ──────────────────────────────────────────────────── */

	#[DataProvider( 'file_kinds' )]
	public function test_file_kind( string $filename, string $expected ): void {
		$this->assertSame( $expected, FileType::kind( $filename ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function file_kinds(): array {
		return array(
			'jpeg'    => array( 'photo.jpg', 'image' ),
			'mp4'     => array( 'clip.mp4', 'video' ),
			'wav'     => array( 'track.wav', 'audio' ),
			'pdf'     => array( 'invoice.pdf', 'document' ),
			'csv'     => array( 'export.csv', 'sheet' ),
			'keynote' => array( 'deck.key', 'slides' ),
			'zip'     => array( 'bundle.zip', 'archive' ),
			'php'     => array( 'index.php', 'code' ),
			'woff'    => array( 'font.woff2', 'font' ),
			'unknown' => array( 'thing.xyz', 'other' ),
			'none'    => array( 'README', 'other' ),
			'path'    => array( '/a/b/photo.PNG', 'image' ),
			'url'     => array( 'https://x.com/a/photo.jpg?v=2', 'image' ),
		);
	}

	#[DataProvider( 'mime_kinds' )]
	public function test_kind_from_mime( string $mime, string $expected ): void {
		$this->assertSame( $expected, FileType::kind_of_mime( $mime ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function mime_kinds(): array {
		return array(
			'image'   => array( 'image/png', 'image' ),
			'video'   => array( 'video/mp4', 'video' ),
			'audio'   => array( 'audio/wav', 'audio' ),
			'pdf'     => array( 'application/pdf', 'document' ),
			'text'    => array( 'text/plain', 'document' ),
			'zip'     => array( 'application/zip', 'archive' ),
			'excel'   => array( 'application/vnd.ms-excel', 'sheet' ),
			'json'    => array( 'application/json', 'code' ),
			'unknown' => array( 'application/octet-stream', 'other' ),
		);
	}

	public function test_labels_name_the_extension(): void {
		$this->assertSame( 'PDF document', FileType::label( 'invoice.pdf' ) );
		$this->assertSame( 'ZIP archive', FileType::label( 'bundle.zip' ) );
		$this->assertSame( 'File', FileType::label( 'README' ) );
	}

	public function test_extension_extraction(): void {
		$this->assertSame( 'jpg', FileType::extension( 'photo.jpg' ) );
		$this->assertSame( 'jpg', FileType::extension( '/a/b/PHOTO.JPG' ) );
		$this->assertSame( 'jpg', FileType::extension( 'https://x.com/p.jpg?v=1#x' ) );
		$this->assertSame( '', FileType::extension( 'README' ) );
		$this->assertSame( '', FileType::extension( 'archive.verylongextension' ) );
	}

	public function test_pretty_labels(): void {
		$this->assertSame( 'x.com', FileType::pretty( 'https://x.com/' ) );
		$this->assertSame( 'Massive Pack.zip', FileType::pretty( 'Presets/Massive Pack.zip' ) );
		$this->assertSame( '—', FileType::pretty( '' ) );
	}

	/**
	 * The extension is preserved so a filename column does not read like
	 * the title column above it.
	 */
	public function test_pretty_keeps_the_extension_after_stripping_a_prefix(): void {
		$this->assertSame(
			'Future Dance.zip',
			FileType::pretty( 'Presets/Acme Samples - Future Dance.zip', strip: 'Acme Samples' )
		);
	}
}
