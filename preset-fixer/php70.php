<?php

declare(strict_types=1);

$config = require __DIR__ . '/base.php';

$rules = [
	// declare(strict_types=1);
	'declare_strict_types' => true,

	// Use `null` coalescing operator `??` where possible
	'ternary_to_null_coalescing' => true,

	'combine_nested_dirname' => true,

	'pow_to_exponentiation' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
