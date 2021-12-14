<?php

declare(strict_types=1);

return [
	'@PSR12' => true,
	'@PSR12:risky' => true,
	'new_with_braces' => false, // new stdClass
	'single_line_after_imports' => false, // Nette uses two empty lines
	'blank_line_after_namespace' => false,
	'ordered_imports' => true, // Use statements are alphabetically ordered
	'blank_line_between_import_groups' => false,

	// overriden rules
	'braces' => false,
	'Nette/braces' => [
		'allow_single_line_closure' => true,
	],

	// In the argument list, there must be one space after each comma, and there must no be a space before each comma
	'method_argument_space' => false,
	'Nette/method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],

	// Spaces should be properly placed in a function declaration.
	'function_declaration' => false,
	'Nette/function_declaration' => true,

	'visibility_required' => false,
	'Nette/class_and_trait_visibility_required' => true,


	// Whitespace

	// Single-line whitespace before closing semicolon are prohibited
	'no_singleline_whitespace_before_semicolons' => true,

	// Fix whitespace after a semicolon
	'space_after_semicolon' => true,

	// Binary operators should be surrounded by at least one space.
	//'binary_operator_spaces' => true,

	// Unary operators should be placed adjacent to their operands.
	'unary_operator_spaces' => true,

	// There MUST NOT be spaces around offset braces $a[0]
	'no_spaces_around_offset' => true,

	// There should not be space before or after object `T_OBJECT_OPERATOR` `->`.
	'object_operator_without_whitespace' => true,

	// Concatenation $a . $b should be spaced according configuration
	'concat_space' => ['spacing' => 'one'],

	// A single space or none should be between cast and variable.
	'cast_spaces' => true,

	// The namespace declaration line shouldn\'t contain leading whitespace
	'no_leading_namespace_whitespace' => true,


	// Control structures

	// Ensure there is no code on the same line as the PHP open tag.
	'linebreak_after_opening_tag' => true,

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

	// Replaces `intval`, `floatval`, `doubleval`, `strval` and `boolval` function calls with according type casting operator
	'modernize_types_casting' => true,

	// Short cast `bool` using double exclamation mark should not be used
	'no_short_bool_cast' => true,

	// Remove useless semicolon statements
	'no_empty_statement' => true,

	'no_unneeded_curly_braces' => true,

	// Remove trailing commas in list() calls.
	'no_trailing_comma_in_list_call' => true,

	// Removes unneeded parentheses around control statements.
	'no_unneeded_control_parentheses' => true,

	// The structure body must be indented once.
	// The closing brace must be on the next line after the body.
	// There should not be more than one statement per line.

	'no_break_comment' => [
		'comment_text' => 'break omitted',
	],

	// Increment and decrement operators should be used if possible.
	'standardize_increment' => true,

	// Magic constants should be referred to using the correct casing.
	'magic_constant_casing' => true,


	// Comments

	// There should not be any empty comments.
	'no_empty_comment' => true,

	// Single-line comments comments with only one line of actual content should use the `//` syntax.
	'single_line_comment_style' => [
		'comment_types' => ['hash'],
	],


	// Arrays

	'no_whitespace_before_comma_in_array' => true,
	'array_indentation' => true,
	'trim_array_spaces' => true,
	'whitespace_after_comma_in_array' => true,

	// commas
	'trailing_comma_in_multiline' => ['elements' => ['arrays']],
	'no_trailing_comma_in_singleline_array' => true,

	'array_syntax' => ['syntax' => 'short'],

	// $arr{} to $arr[]
	'normalize_index_brace' => true,


	// Strings

	// Convert `heredoc` to `nowdoc` where possible.
	'heredoc_to_nowdoc' => true,

	// Convert double quotes to single quotes for simple strings.
	'single_quote' => true,

	'escape_implicit_backslashes' => true,


	// PHPDoc

	// Docblocks should have the same indentation as the documented subject.
	'phpdoc_indent' => true,

	// There should not be empty PHPDoc blocks.
	'no_empty_phpdoc' => true,

	// Phpdocs should start and end with content, excluding the very first and last line of the docblocks.
	'phpdoc_trim' => true,

	'phpdoc_trim_consecutive_blank_line_separation' => true,

	'phpdoc_types' => true,


	// Classes

	// class element order: constants, properties, from public to private
	'ordered_class_elements' => [
		'order' => [
			'use_trait',
			'constant',
			'constant_public',
			'constant_protected',
			'constant_private',
			'property_public',
			'property_protected',
			'property_private',
		],
	],

	// Properties MUST not be explicitly initialized with `null`.
	'no_null_property_initialization' => true,

	// Constructor having promoted properties must have them in separate lines
	PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer::name() => true,
];
