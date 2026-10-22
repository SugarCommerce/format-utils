<?php
/**
 * A CSV without the columns it was read for.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.7.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format;

/**
 * Class MissingColumns
 *
 * Thrown by {@see Csv::read()} before the first row, so a file in the wrong
 * format is refused as a whole instead of being read as thousands of rows
 * of blanks. Carries which columns were missing, for the message.
 *
 * @since 1.7.0
 */
final class MissingColumns extends \UnexpectedValueException {

	/**
	 * @since 1.7.0
	 *
	 * @param list<string> $missing The required columns the header lacks.
	 * @param list<string> $found   The header as read.
	 */
	public function __construct(
		public readonly array $missing,
		public readonly array $found,
	) {
		parent::__construct( 'Missing CSV columns: ' . implode( ', ', $missing ) . '.' );
	}
}
