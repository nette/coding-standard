<?php

declare(strict_types=1);

$config = require __DIR__ . '/php74.php';

$rules = [
	'@PHP80Migration' => true,
	'@PHP80Migration:risky' => true,
	'void_return' => false,
];

$config->setRules($rules + $config->getRules());
return $config;
