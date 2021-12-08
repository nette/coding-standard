<?php

declare(strict_types=1);

$config = require __DIR__ . '/php73.php';

$rules = [
	'@PHP74Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
