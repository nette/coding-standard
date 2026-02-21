<?php declare(strict_types=1);

$config = require __DIR__ . '/php80.php';

$rules = [
	'@PHP8x1Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
