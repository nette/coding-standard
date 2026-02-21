<?php declare(strict_types=1);

$config = require __DIR__ . '/php83.php';

$rules = [
	'@PHP8x4Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
