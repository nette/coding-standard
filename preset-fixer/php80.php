<?php declare(strict_types=1);

$config = require __DIR__ . '/base.php';

foreach (glob(__DIR__ . '/common/*.php') as $file) {
	$config->setRules(array_merge($config->getRules(), require $file));
}

$rules = [
	'void_return' => false,
];

$config->setRules(array_merge($rules, $config->getRules(), $customRules));
return $config;
