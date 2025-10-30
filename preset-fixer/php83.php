<?php

declare(strict_types=1);

$config = require __DIR__ . '/php82.php';

$rules = [
	'@PHP8x3Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
