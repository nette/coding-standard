<?php

declare(strict_types=1);

$config = require __DIR__ . '/base.php';

$rules = [
	'strict_comparison' => true,

	// There should not be useless `else` cases.
	'no_useless_else' => true,

	// Internal classes should be `final`
	'final_internal_class' => true,

	// Properties should be set to `null` instead of using `unset`
	'no_unset_on_property' => true,

	// reformat
	'Nette/braces' => ['allow_single_line_closure' => true],
	'no_whitespace_in_blank_line' => true,
	'no_trailing_whitespace' => true,
];

$config->setRules(array_merge($rules, $customRules));
return $config;
