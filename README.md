# Format Utils

Display formatting: byte sizes, durations, compact numbers, word-safe truncation, deterministic initials avatars, and file-type labels. Parses back what it formats. Zero dependencies.

## Why

Every application rewrites these, and each rewrite is subtly worse than the last: a truncation that counts bytes and cuts a multi-byte character in half, a `100.0 MB` nobody wanted, an ordinal that renders `11st`, an avatar palette that reshuffles every time the list paginates.

None of it is hard. It is just never worth anybody's afternoon, so it gets done in five minutes and stays wrong.

## Features

- 💾 **Bytes both ways** — binary and decimal units, and a parser that reads `2 MB` back to `2097152`.
- ⏱️ **Durations in three registers** — `4:03` for media, `2h 5m` for tables, `about two hours` for prose.
- ✂️ **Truncation that counts characters** — never splits a word, never splits a codepoint, and includes the ellipsis in the budget.
- 🔢 **Compact numbers** — `1.2k`, `3.4M`, plus percentages and period-over-period change.
- 🎨 **Deterministic avatars** — an SVG data URI from a name; the same name is always the same colour.
- 📁 **File kinds** — coarse buckets for grouping and filtering, from a filename or a MIME type; the content type for a name, and whether every browser shows it in place.
- 🔎 **Forgiving search** — every word typed, in any order, one typo forgiven (a swap of neighbours counts as one), accents ignored.

## Requirements

PHP 8.5+

## Installation

```bash
composer require sugarcommerce/format-utils
```

## Bytes

```php
use SugarCommerce\Format\Bytes;

Bytes::format( 1572864 );                  // '1.5 MB'
Bytes::format( 104857600 );                // '100 MB'  — not '100.0 MB'
Bytes::format( 1000, decimal: true );      // '1 kB'
Bytes::parse( '2 MB' );                    // 2097152
Bytes::parse( '1,024 KB' );                // 1048576
Bytes::rate( 1000000, 2.0 );               // '500 kB/s'
```

Binary (1 KB = 1024) is the default because this is usually describing a file, and it is what a filesystem reports and what an upload limit means. Decimal (1 kB = 1000) is what a disk manufacturer prints on the box and what a transfer rate uses — which is the entire content of "why does my 500 GB drive show 465 GB?":

```php
Bytes::format( 500 * (1000 ** 3), decimal: true );  // '500 GB'
Bytes::format( 500 * (1000 ** 3) );                 // '465.7 GB'
```

`parse()` reads `KB` and `KiB` alike as 1024, because people writing `KB` in a config file mean the binary unit.

In another language, the same size with the language's own separators (needs `ext-intl`; without it, and in English, it is exactly `format()`):

```php
Bytes::format_localized( 1572864, 'de' );              // '1,5 MB'
Bytes::format_localized( 1024 * 1000 + 512, 'de' );    // '1.000,5 KB' — grouping too, not a comma swapped for a point
```

## Durations

```php
use SugarCommerce\Format\Duration;

Duration::clock( 243 );      // '4:03'      — media, aligned
Duration::clock( 3723 );     // '1:02:03'
Duration::compact( 7500 );   // '2h 5m'     — tables
Duration::words( 7500 );     // 'about 2 hours' — prose
Duration::parse( '2h 5m' );  // 7500
```

`clock()` omits the hour below an hour, because `0:04:03` on a four-minute track is noise. `compact()` shows two units by default, since the third never changes a decision. `words()` is deliberately imprecise: where a duration is read rather than measured, a rounded phrase carries the meaning and a precise one invites arithmetic the reader did not want to do.

## Text

```php
use SugarCommerce\Format\Text;

Text::truncate( $title, 40 );              // word-safe, ellipsis inside the budget
Text::excerpt( $body, 160 );               // collapses newlines first
Text::initials( 'Dave Sherlock' );         // 'DS' — first and last, for a name
Text::initials( 'Trance Vol 1', from_start: true ); // 'TV' — first two, for a title
Text::list( ['a', 'b', 'c'] );             // 'a, b and c'
Text::list( $tags, limit: 2 );             // 'design, audio and 4 more'
Text::plural( 3, 'entry' );                // '3 entries'
Text::plural( 2, 'person', 'people' );     // '2 people'
Text::ordinal( 21 );                       // '21st'
Text::ordinal( 11 );                       // '11th'  — the teens are the trap
Text::paragraphs( $description );          // ['First line', 'Second line'] — every break is one
Text::humanize( 'sample_rate' );           // 'Sample rate' — a key as a label; 'BPM' stays 'BPM'
```

Truncation counts characters, not bytes, so a limit of 20 means twenty visible characters whether the text is English or Japanese. It only breaks at a word boundary if one is reasonably near the end — otherwise a long unbroken string would collapse to almost nothing.

`plural()` handles the common English endings and is not a linguistics engine: `person` becomes `persons` unless you say otherwise.

**Everything here returns plain text and escapes nothing.** Escape at the point of output, for the encoding of the place it is going.

## Numbers

```php
use SugarCommerce\Format\Numbers;

Numbers::compact( 1200 );              // '1.2k'
Numbers::compact( 3400000 );           // '3.4M'
Numbers::compact( 847 );               // '847'   — already readable
Numbers::percent( 42.4 );              // '42%'
Numbers::share( 25, 100 );             // '25%'
Numbers::share( 5, 0 );                // '—'     — a share of nothing is undefined
Numbers::change( 120, 100 );           // '+20%'
Numbers::change( 50, 0 );              // 'new'
```

For money use [`sugarcommerce/currency`](https://github.com/sugarcommerce/currency) — money is integer minor units and needs its own rules.

## Avatars

```php
use SugarCommerce\Format\Avatar;

Avatar::svg( 'Dave Sherlock' );        // data:image/svg+xml;utf8,… → straight into <img src>
Avatar::hue( 'Dave Sherlock' );        // 214 — tint something else to match
Avatar::colour( 'Dave Sherlock' );     // 'hsl(214,55%,42%)'
```

Deterministic: the same name always yields the same colour, so a list does not reshuffle its palette as you scroll or paginate. The hue comes from a hash of the name; saturation and lightness are fixed, which keeps every generated colour in one family instead of producing the occasional neon.

No network request, no upload, nothing to store. The initials are XML-escaped, because the name is user data being written into markup.

## File types

```php
use SugarCommerce\Format\FileType;

FileType::kind( 'photo.jpg' );                       // 'image'
FileType::kind( 'https://x.com/a/photo.jpg?v=2' );   // 'image'
FileType::kind_of_mime( 'application/pdf' );         // 'document'
FileType::label( 'invoice.pdf' );                    // 'PDF document'
FileType::extension( '/a/b/PHOTO.JPG' );             // 'jpg'
FileType::kinds();                                   // key => label, for a filter
FileType::pretty( 'https://x.com/' );                // 'x.com'
FileType::pretty( 'Presets/Massive Pack.zip' );      // 'Massive Pack.zip'
FileType::mime( 'Master.wav' );                      // 'audio/wav' — 'application/octet-stream' when unknown
FileType::inline( 'teaser.mov' );                    // 'video/quicktime'
FileType::inline( 'session.aiff' );                  // '' — Safari plays it, nobody else does
```

`inline()` is the type to give an `<img>`, `<video>`, `<audio>` or PDF viewer, and only for files *every* current browser shows in place: PNG, JPEG, GIF, WebP, AVIF; MP4, WebM, MOV; MP3, WAV, M4A, AAC, Ogg, Opus, FLAC; PDF. Not SVG — a document that can carry script. Not TIFF, HEIC, PSD or AIFF — most browsers show nothing, and a preview offered to half the people who open it is worse than an icon.

Kinds are deliberately coarse — `image`, `video`, `audio`, `document`, `sheet`, `slides`, `archive`, `code`, `font`, `other`. A file list wants six or seven buckets a person can filter by, not the ninety distinct types a MIME database knows about.

> **Never decide whether an upload is safe from its extension.** The extension is chosen by whoever uploaded it. This is presentation only; sniff the contents.

`pretty()` takes out control characters and the invisible marks that reorder text, so `invoice` + U+202E + `fdp.exe` cannot display as `invoiceexe.pdf`. It keeps the extension after stripping a prefix, because without it a filename column reads like the title column above it — which is exactly the confusion it was written to fix.

## Search

```php
use SugarCommerce\Format\Search;

Search::filter( $files, 'licens drive', field: 'filename' );   // rows whose filename has every word
Search::matches( 'Neon Rain - Master.wav', 'rian mastr' );       // true
Search::distance( 'midnigth', 'midnight' );                       // 1
Search::words( 'Midnight Drive — Master.WAV' );                   // [ 'midnight', 'drive', 'master', 'wav' ]
Search::fold( 'RÉSUMÉ' );                                         // 'resume'
```

For somebody searching a list of their own things, who remembers roughly what one is called and not how they spelled it: "license" finds "Licence Agreement.pdf", "drive master" finds "Midnight Drive - Master.wav". Every word typed must be in the name, in any order — inside it as typed, or, from four letters, one edit from a word of the name or from the start of one (so "licens" works before the typing is finished). Below four letters nothing is guessed: "way" is not "wav".

The edit distance is optimal string alignment by character, not PHP's `levenshtein()` by byte: an accented letter is one character, and two neighbouring letters the wrong way round — the commonest slip, "midnigth" — is one edit rather than two. `@arraypress/text`'s `matchesAll()` follows the same rules, so a browser-side filter finds what the server does.

## CSV

For exports people open in a spreadsheet:

```php
use SugarCommerce\Format\Csv;

$csv = Csv::build( array( 'email', 'orders' ), $rows );
```

Any text cell starting with `=`, `+`, `-`, `@`, a tab or a carriage return — or with one of those after leading whitespace an importer might strip — gets a leading apostrophe, so a value like `=HYPERLINK(…)` opens as text instead of running as a formula (CSV injection). Numbers stay numbers. A cell with a semicolon in it is quoted, so Excel set to split on semicolons, as it is across much of Europe, cannot split one cell into two and start the second with `=`. The file starts with a UTF-8 byte-order mark so Excel reads accents correctly; pass `bom: false` for anything that is not a spreadsheet.

## Testing

```bash
composer install
composer test
```

225 tests — both byte conventions and their disagreement, localized sizes, round trips through every parser, multi-byte truncation, the ordinal teens, avatar determinism and escaping, file kinds and content types from paths, URLs and MIME types, and search: typos, swaps, accents, word order and the short-word rule.

## License

GPL-2.0-or-later
