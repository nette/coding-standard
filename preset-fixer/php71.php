<?php

declare(strict_types=1);

$config = require __DIR__ . '/base.php';

foreach (glob(__DIR__ . '/common/*.php') as $file) {
	$config->setRules(array_merge($config->getRules(), require $file));
}

$rules = [
	'@PHP71Migration' => true,
	'@PHP70Migration:risky' => true,
	'random_api_migration' => false,
	'non_printable_character' => false, // not working properly
];

$config->setRules(array_merge($rules, $config->getRules(), $customRules));
return $config;
