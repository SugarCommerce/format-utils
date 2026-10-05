<?php
/**
 * File kinds and labels.
 *
 * @package   SugarCommerce\Format
 * @copyright Copyright (c) 2026, ArrayPress Limited
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

declare( strict_types=1 );

namespace SugarCommerce\Format;

/**
 * Class FileType
 *
 * Maps a filename or MIME type to a coarse kind, for grouping, filtering
 * and choosing an icon.
 *
 * The kind is deliberately coarse. A file list wants six or seven
 * buckets a person can filter by, not the ninety distinct types a MIME
 * database knows about.
 *
 * This is presentation only. **Never** decide whether an upload is safe
 * from its extension — the extension is chosen by whoever uploaded it.
 * Sniff the contents.
 *
 * @since 1.0.0
 */
final readonly class FileType {

	/**
	 * Extensions per kind.
	 */
	private const array KINDS = array(
		'image'    => array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'ico', 'heic', 'tiff', 'tif', 'psd', 'ai', 'eps' ),
		'video'    => array( 'mp4', 'mov', 'webm', 'mkv', 'avi', 'm4v', 'mpg', 'mpeg', 'wmv', 'flv' ),
		'audio'    => array( 'mp3', 'wav', 'flac', 'aac', 'ogg', 'oga', 'm4a', 'aiff', 'aif', 'wma', 'opus', 'alac', 'mid', 'midi' ),
		'document' => array( 'pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'pages', 'epub' ),
		'sheet'    => array( 'xls', 'xlsx', 'ods', 'csv', 'tsv', 'numbers' ),
		'slides'   => array( 'ppt', 'pptx', 'odp', 'key' ),
		'archive'  => array( 'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'dmg', 'iso' ),
		'code'     => array( 'php', 'js', 'ts', 'jsx', 'tsx', 'css', 'scss', 'html', 'json', 'xml', 'yml', 'yaml', 'sh', 'sql', 'py', 'rb', 'go', 'rs', 'swift', 'java', 'c', 'cpp', 'h' ),
		'font'     => array( 'ttf', 'otf', 'woff', 'woff2', 'eot' ),
	);

	/**
	 * Content types by extension, for the files people commonly sell, send
	 * and attach. Anything missing is application/octet-stream.
	 */
	private const array MIME = array(
		// Images.
		'png'  => 'image/png',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif'  => 'image/gif',
		'webp' => 'image/webp',
		'avif' => 'image/avif',
		'svg'  => 'image/svg+xml',
		'bmp'  => 'image/bmp',
		'ico'  => 'image/vnd.microsoft.icon',
		'heic' => 'image/heic',
		'tif'  => 'image/tiff',
		'tiff' => 'image/tiff',
		'psd'  => 'image/vnd.adobe.photoshop',
		'ai'   => 'application/postscript',
		'eps'  => 'application/postscript',
		// Video.
		'mp4'  => 'video/mp4',
		'm4v'  => 'video/mp4',
		'mov'  => 'video/quicktime',
		'webm' => 'video/webm',
		'mkv'  => 'video/x-matroska',
		'avi'  => 'video/x-msvideo',
		'mpg'  => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'wmv'  => 'video/x-ms-wmv',
		'flv'  => 'video/x-flv',
		// Audio.
		'mp3'  => 'audio/mpeg',
		'wav'  => 'audio/wav',
		'm4a'  => 'audio/mp4',
		'alac' => 'audio/mp4',
		'aac'  => 'audio/aac',
		'ogg'  => 'audio/ogg',
		'oga'  => 'audio/ogg',
		'opus' => 'audio/ogg',
		'flac' => 'audio/flac',
		'aif'  => 'audio/aiff',
		'aiff' => 'audio/aiff',
		'wma'  => 'audio/x-ms-wma',
		'mid'  => 'audio/midi',
		'midi' => 'audio/midi',
		// Documents.
		'pdf'  => 'application/pdf',
		'txt'  => 'text/plain',
		'md'   => 'text/markdown',
		'csv'  => 'text/csv',
		'rtf'  => 'application/rtf',
		'epub' => 'application/epub+zip',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls'  => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt'  => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'odt'  => 'application/vnd.oasis.opendocument.text',
		'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
		'odp'  => 'application/vnd.oasis.opendocument.presentation',
		// Archives.
		'zip'  => 'application/zip',
		'rar'  => 'application/vnd.rar',
		'7z'   => 'application/x-7z-compressed',
		'tar'  => 'application/x-tar',
		'gz'   => 'application/gzip',
		'tgz'  => 'application/gzip',
		'bz2'  => 'application/x-bzip2',
		'xz'   => 'application/x-xz',
		// Fonts.
		'ttf'   => 'font/ttf',
		'otf'   => 'font/otf',
		'woff'  => 'font/woff',
		'woff2' => 'font/woff2',
	);

	/**
	 * Extensions every current browser shows or plays in place, given the
	 * type from MIME.
	 *
	 * Deliberately short. Not SVG: it is a document that can carry script.
	 * Not TIFF, HEIC or PSD: most browsers draw nothing. Not AIFF: only
	 * Safari plays it. MOV is QuickTime, which plays where its codec is
	 * H.264 — most of them — and shows the player's own error otherwise.
	 * FLAC and Opus are in every current browser now.
	 */
	private const array INLINE = array(
		'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif',
		'mp4', 'm4v', 'webm', 'mov',
		'mp3', 'wav', 'm4a', 'aac', 'ogg', 'oga', 'opus', 'flac',
		'pdf',
	);

	/**
	 * Human labels per kind.
	 */
	private const array LABELS = array(
		'image'    => 'Image',
		'video'    => 'Video',
		'audio'    => 'Audio',
		'document' => 'Document',
		'sheet'    => 'Spreadsheet',
		'slides'   => 'Presentation',
		'archive'  => 'Archive',
		'code'     => 'Code',
		'font'     => 'Font',
		'other'    => 'File',
	);

	/**
	 * The kind of a file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string One of the kind keys, or `other`.
	 */
	public static function kind( string $filename ): string {
		$extension = self::extension( $filename );

		if ( '' === $extension ) {
			return 'other';
		}

		foreach ( self::KINDS as $kind => $extensions ) {
			if ( in_array( $extension, $extensions, true ) ) {
				return $kind;
			}
		}

		return 'other';
	}

	/**
	 * The kind implied by a MIME type.
	 *
	 * More trustworthy than the extension when it came from content
	 * sniffing, and no more trustworthy when it came from the browser.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string
	 */
	public static function kind_of_mime( string $mime_type ): string {
		$mime_type = strtolower( trim( $mime_type ) );
		[ $top ]   = array_pad( explode( '/', $mime_type, 2 ), 2, '' );

		return match ( true ) {
			'image' === $top => 'image',
			'video' === $top => 'video',
			'audio' === $top => 'audio',
			'font' === $top  => 'font',
			str_contains( $mime_type, 'zip' ), str_contains( $mime_type, 'compressed' ), str_contains( $mime_type, 'tar' ) => 'archive',
			str_contains( $mime_type, 'spreadsheet' ), str_contains( $mime_type, 'excel' ), str_contains( $mime_type, 'csv' ) => 'sheet',
			str_contains( $mime_type, 'presentation' ), str_contains( $mime_type, 'powerpoint' ) => 'slides',
			str_contains( $mime_type, 'pdf' ), str_contains( $mime_type, 'word' ), str_contains( $mime_type, 'document' ), 'text' === $top => 'document',
			str_contains( $mime_type, 'json' ), str_contains( $mime_type, 'xml' ), str_contains( $mime_type, 'javascript' ) => 'code',
			default => 'other',
		};
	}

	/**
	 * A human label for a file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string e.g. `PDF document`, `Image`.
	 */
	public static function label( string $filename ): string {
		$extension = self::extension( $filename );
		$kind      = self::kind( $filename );
		$label     = self::LABELS[ $kind ] ?? 'File';

		if ( '' === $extension ) {
			return $label;
		}

		return strtoupper( $extension ) . ' ' . strtolower( $label );
	}

	/**
	 * The lowercase extension.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string Empty when there is none.
	 */
	public static function extension( string $filename ): string {
		// Query strings and fragments are common when the "filename" is
		// really a URL, and neither is part of the name.
		$filename = (string) preg_replace( '/[?#].*$/', '', trim( $filename ) );
		$base     = basename( str_replace( '\\', '/', $filename ) );

		if ( ! str_contains( $base, '.' ) ) {
			return '';
		}

		$extension = strtolower( substr( $base, strrpos( $base, '.' ) + 1 ) );

		return 1 === preg_match( '/^[a-z0-9]{1,10}$/', $extension ) ? $extension : '';
	}

	/**
	 * The content type for a file, from its name.
	 *
	 * For a Content-Type header on something you serve or store, where the
	 * name is the only description there is. The same warning as kind():
	 * this says what the file claims to be, not what it is.
	 *
	 * @since 1.2.0
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string e.g. `audio/mpeg`; `application/octet-stream` when the
	 *                extension is unknown or missing.
	 */
	public static function mime( string $filename ): string {
		return self::MIME[ self::extension( $filename ) ] ?? 'application/octet-stream';
	}

	/**
	 * The content type to show or play a file in the browser as, or ''.
	 *
	 * For an `<img>`, `<video>`, `<audio>` or a PDF viewer: only types every
	 * current browser handles in place, so a preview is never offered to half
	 * the people who open it. Every browser decides from the type it is told,
	 * which is why this goes by name rather than by sniffing.
	 *
	 * @since 1.2.0
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string e.g. `video/quicktime`, or '' when it would not show.
	 */
	public static function inline( string $filename ): string {
		$extension = self::extension( $filename );

		return in_array( $extension, self::INLINE, true ) ? self::MIME[ $extension ] : '';
	}

	/**
	 * Every kind, as key => label, for a filter control.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function kinds(): array {
		return self::LABELS;
	}

	/**
	 * Tidy a path or URL into something readable.
	 *
	 * A URL loses its scheme and trailing slash so it reads as a host; a
	 * path keeps only its basename. The extension is preserved either
	 * way, because without it a filename column looks like a title column.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path   Path or URL.
	 * @param string $strip  Optional prefix to remove from the basename,
	 *                       for stripping a brand or a folder convention.
	 *
	 * @return string `—` when empty.
	 */
	public static function pretty( string $path, string $strip = '' ): string {
		$path = trim( $path );

		if ( '' === $path ) {
			return '—';
		}

		if ( 1 === preg_match( '#^https?://#i', $path ) ) {
			return rtrim( (string) preg_replace( '#^https?://#i', '', $path ), '/' );
		}

		$base = basename( str_replace( '\\', '/', $path ) );

		if ( '' !== $strip ) {
			$base = (string) preg_replace( '/^' . preg_quote( $strip, '/' ) . '\s*-?\s*/i', '', $base );
		}

		return '' === $base ? '—' : $base;
	}
}
