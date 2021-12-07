<?php

declare(strict_types=1);

return [
	// PHP code must use only UTF-8 without BOM
	'encoding' => true,

	// <?php opening tag
	'full_opening_tag' => true,

	// Ensure there is no code on the same line as the PHP open tag.
	'linebreak_after_opening_tag' => true,

	// The closing ? > tag must be omitted from files containing only PHP.
	'no_closing_tag' => true,

	// There must not be trailing whitespace at the end of lines.
	'no_trailing_whitespace' => true,

	// ...and at the end of blank lines.
	'no_whitespace_in_blank_line' => true,

	// All files must end with a single blank line.
	'single_blank_line_at_eof' => true,

	// File name should match class name if possible.
	//'psr4' => true,
];
