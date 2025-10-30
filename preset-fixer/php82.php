<?php

declare(strict_types=1);

$config = require __DIR__ . '/php81.php';

$rules = [
	'@PHP8x2Migration' => true,
];

$config->setRules($rules + $config->getRules());
return $config;
