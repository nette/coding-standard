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
];