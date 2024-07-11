<?php

declare(strict_types=1);

$files = file(__DIR__ . '/../filelist.tmp', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$files = array_map(function ($path) {
	return new SplFileInfo($path);
}, $files);

$config = new PhpCsFixer\Config;
$config->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());
$config->registerCustomFixers([
	new NetteCodingStandard\Fixer\ClassNotation\ClassAndTraitVisibilityRequiredFixer,
	new NetteCodingStandard\Fixer\FunctionNotation\MethodArgumentSpaceFixer,
	new NetteCodingStandard\Fixer\FunctionNotation\FunctionDeclarationFixer,
]);
$config->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers);
$config->setUsingCache(false);
$config->setIndent("\t");
$config->setLineEnding(PHP_EOL);
$config->setRiskyAllowed(true);
$config->setFinder($files);


$customRules = [];
$root = getcwd();
while (!is_file("$root/ncs.php") && substr_count($root, DIRECTORY_SEPARATOR) > 1) {
	$root = dirname($root);
}
if (is_file($file = "$root/ncs.php")) {
	echo "used $file\n";
	$customRules = require $file;
}

$config->setRules([]);

return $config;
