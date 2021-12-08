<?php

declare(strict_types=1);

$config = require __DIR__ . '/php80.php';

$rules = [
	'@PHP81Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
