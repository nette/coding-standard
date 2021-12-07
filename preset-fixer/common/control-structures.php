<?php

declare(strict_types=1);

return [
	'no_alternative_syntax' => true,

	// Calling `unset` on multiple items should be done in one call.
	'combine_consecutive_unsets' => true,

	// Replace all `<>` with `!=`.
	'standardize_not_equals' => true,

	// Include/Require and file path should be divided with a single space. File path should not be placed under brackets.
	'include' => true,

	// Inside a classy element "self" should be preferred to the class name itself.
	'self_accessor' => true,

	// Function defined by PHP should be called using the correct casing
	'native_function_casing' => true,

	// PHP keywords must be in lower case
	'lowercase_keywords' => true,

	// The PHP constants `true`, `false`, and `null` MUST be in lower case
	'lowercase_constants' => true,

	// Cast should be written in lower case.
	'lowercase_cast' => true,

	// Replaces `intval`, `floatval`, `doubleval`, `strval` and `boolval` function calls with according type casting operator
	'modernize_types_casting' => true,

	// Short cast `bool` using double exclamation mark should not be used
	'no_short_bool_cast' => true,

	// Cast `(boolean)` and `(integer)` should be written as `(bool)` and `(int)`, `(double)` and `(real)` as `(float)`
	'short_scalar_cast' => true,

	// The keyword `elseif` should be used instead of `else if` so that all control keywords look like single words.
	'elseif' => true,

	// Remove useless semicolon statements
	'no_empty_statement' => true,

	'no_unneeded_curly_braces' => true,

	// Remove trailing commas in list() calls.
	'no_trailing_comma_in_list_call' => true,

	// Removes unneeded parentheses around control statements.
	'no_unneeded_control_parentheses' => true,

	// A case should be followed by a colon and not a semicolon.
	'switch_case_semicolon_to_colon' => true,

	// The structure body must be indented once.
	// The closing brace must be on the next line after the body.
	// There should not be more than one statement per line.
	'Nette/braces' => [
		'allow_single_line_closure' => true,
	],

	'no_break_comment' => [
		'comment_text' => 'break omitted',
	],

	// Increment and decrement operators should be used if possible.
	'standardize_increment' => true,

	// Magic constants should be referred to using the correct casing.
	'magic_constant_casing' => true,
];
