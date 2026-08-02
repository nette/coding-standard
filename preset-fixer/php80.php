<?php declare(strict_types=1);

$config = require __DIR__ . '/base.php';

foreach (glob(__DIR__ . '/common/*.php') as $file) {
	$config->setRules(array_merge($config->getRules(), require $file));
}

$rules = [
	'void_return' => false,
];

// enabling the stock fixer again would let it run alongside `Nette/ordered_imports`
// (same priority, undefined order) and corrupt comma-separated imports, so it stays off
$enforced = [
	'ordered_imports' => false,
];

$config->setRules(array_merge($rules, $config->getRules(), $customRules, $enforced));
return $config;
