<?php

declare(strict_types=1);

return [
	'dir_constant' => true,
	'logical_operators' => true,
	'no_alias_functions' => true,
	'set_type_to_cast' => true,
	'combine_consecutive_issets' => true,
	'combine_consecutive_unsets' => true,
	'backtick_to_shell_exec' => true,

	// Functions should be used with `$strict` param set to `true`
	'strict_param' => true,

	// replaces is_null(parameter) expression with `null === parameter`.
	'is_null' => true,

	// The configured functions must be commented out
	PhpCsFixerCustomFixers\Fixer\CommentedOutFunctionFixer::name() => ['print_r', 'var_dump', 'var_export', 'dump'],

	// Classes defined internally by extension or core must be referenced with the correct case
	'class_reference_name_casing' => true,

	// Classes in the global namespace cannot contain leading slashes
	PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer::name() => true,
];
