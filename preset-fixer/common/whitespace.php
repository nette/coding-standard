<?php

declare(strict_types=1);

return [
	// SPACES

	// Single-line whitespace before closing semicolon are prohibited
	'no_singleline_whitespace_before_semicolons' => true,

	// Fix whitespace after a semicolon
	'space_after_semicolon' => true,

	// Binary operators should be surrounded by at least one space.
	//'binary_operator_spaces' => true,

	// Unary operators should be placed adjacent to their operands.
	'unary_operator_spaces' => true,

	// No space after the opening parenthesis and before the closing parenthesis
	'no_spaces_inside_parenthesis' => true,

	// There MUST NOT be spaces around offset braces $a[0]
	'no_spaces_around_offset' => true,

	// There should not be space before or after object `T_OBJECT_OPERATOR` `->`.
	'object_operator_without_whitespace' => true,

	// Standardize spaces around ternary operator.
	'ternary_operator_spaces' => true,

	// Concatenation $a . $b should be spaced according configuration
	'concat_space' => ['spacing' => 'one'],

	// Removes extra spaces between colon and case value.
	'switch_case_space' => true,

	// A single space or none should be between cast and variable.
	'cast_spaces' => true,

	// When making a method or function call, there MUST NOT be a space between the method or function name and the opening parenthesis
	'no_spaces_after_function_name' => true,

	'declare_equal_normalize' => ['space' => 'none'],

	// LINES, INDENTATION

	// The namespace declaration line shouldn\'t contain leading whitespace
	'no_leading_namespace_whitespace' => true,

	'indentation_type' => true,


	// FUNCTION

	// In the argument list, there must be one space after each comma, and there must no be a space before each comma
	'Nette/method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],

	// Spaces should be properly placed in a function declaration.
	'Nette/function_declaration' => true,

	// CLASS

	'class_definition' => true,
];
