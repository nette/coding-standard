<?php

declare(strict_types=1);

return [
	// array spacing
	'no_whitespace_before_comma_in_array' => true,
	'array_indentation' => true,
	'trim_array_spaces' => true,
	'whitespace_after_comma_in_array' => true,

	// commas
	'trailing_comma_in_multiline_array' => true,
	'no_trailing_comma_in_singleline_array' => true,

	'array_syntax' => ['syntax' => 'short'],

	// $arr{} to $arr[]
	'normalize_index_brace' => true,
];
