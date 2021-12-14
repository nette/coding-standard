<?php

declare(strict_types=1);

$config = require __DIR__ . '/base.php';

$rules = [
];

$config->setRules(array_merge($rules, $customRules));
return $config;
