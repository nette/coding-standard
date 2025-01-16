<?php

declare(strict_types=1);

$config = require __DIR__ . '/php83.php';

$rules = [
	'@PHP84Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
