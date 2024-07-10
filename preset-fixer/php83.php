<?php

declare(strict_types=1);

$config = require __DIR__ . '/php82.php';

$rules = [
	'@PHP83Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
