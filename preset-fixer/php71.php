<?php

declare(strict_types=1);

$config = require __DIR__ . '/php70.php';

foreach (glob(__DIR__ . '/common/*.php') as $file) {
	$config->setRules(array_merge($config->getRules(), require $file));
}

$rules = [
	// Formatting - rules for consistent code looks

	'Nette/class_and_trait_visibility_required' => [
		'elements' => ['const', 'property', 'method'],
	],

	// short list() syntax []
	'list_syntax' => ['syntax' => 'short'],
];

$config->setRules(array_merge($rules, $config->getRules(), $customRules));
return $config;
