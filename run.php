<?php declare(strict_types=1);

use Nette\CommandLine\Parser;

const VERSION = '3.5.0';

// Autoloader
if (
	!(is_file($file = ($vendorDir = __DIR__ . '/vendor') . '/autoload.php') && include $file) &&
	!(is_file($file = ($vendorDir = __DIR__ . '/../..') . '/autoload.php') && include $file)
) {
	fwrite(STDERR, "Install packages using Composer.\n");
	exit(1);
}


// Argument Parsing
$cmd = new Parser(<<<'XX'

	Usage:
	    ecs [check | fix] [options] [<path>...]

	Options:
	    --preset <name>       Specify preset (e.g., php81). Autodetected if omitted.
	    --config-file <path>  Additional config file (.php for PHP CS Fixer, .xml for PHP_CodeSniffer).
	                          May be given twice (once per tool).
	    --fix                 Shortcut for 'fix' mode.
	    -h | --help           Show this help.
	    -V | --version        Show version information.


	XX, [
	'--config-file' => [Parser::RealPath => true, Parser::Repeatable => true],
	'path' => [Parser::Repeatable => true, Parser::Optional => true],
]);

try {
	$options = $cmd->parse();
} catch (Throwable $e) {
	fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
	exit(1);
}

if ($cmd->isEmpty() || !empty($options['--help'])) {
	echo 'Nette Coding Standard ' . VERSION . "\n";
	$cmd->help();
	exit(0);
}
if (!empty($options['--version'])) {
	echo 'Nette Coding Standard ' . VERSION . "\n";
	exit(0);
}

$paths = $options['path'] ?? [];
$dryRun = true;
if (!empty($paths) && in_array($paths[0], ['check', 'fix'], true)) {
	$dryRun = array_shift($paths) === 'check';
}
if (!empty($options['--fix'])) {
	$dryRun = false;
}

$preset = $options['--preset'] ?? null;
$configFilePhp = $configFileXml = null;
foreach ($options['--config-file'] ?? [] as $path) {
	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
	if ($ext === 'php') {
		$configFilePhp = $path;
	} elseif ($ext === 'xml') {
		$configFileXml = $path;
	} else {
		fwrite(STDERR, "Config file must have .php or .xml extension: $path\n");
		exit(1);
	}
}


// Determine Project Root (essential for finding composer.json and relative paths)
$root = getcwd(); // Start from current working directory
while (!is_file("$root/composer.json") && substr_count($root, DIRECTORY_SEPARATOR) > 1) {
	$root = dirname($root);
}
if (!is_file("$root/composer.json")) {
	$root = getcwd();
	echo "Warning: Could not find composer.json, using current directory '{$root}' as project root.\n";
}



// Instantiate and Configure Checker
if ($configFilePhp !== null) {
	putenv('NCS_CONFIG_FILE_PHP=' . $configFilePhp);
	echo "Additional fixer config: $configFilePhp\n";
}
if ($configFileXml !== null) {
	echo "Additional sniffer config: $configFileXml\n";
}
$checker = new Checker($vendorDir, $root, $dryRun, $preset, $configFileXml);
echo 'Mode: ' . ($dryRun ? 'Check (dry-run)' : 'Fix') . "\n";

// Determine and set paths
$paths = $paths ?: array_filter(['src', 'tests'], 'is_dir') ?: ['.'];
$checker->setPaths($paths);
echo 'Paths: ' . implode(', ', $paths) . "\n";
if ($preset) {
	echo "Preset: {$preset}\n";
}

// Signal Handling
if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGINT, function () use ($checker) {
		pcntl_signal(SIGINT, SIG_DFL);
		throw new Exception;
	});
} elseif (function_exists('sapi_windows_set_ctrl_handler')) {
	sapi_windows_set_ctrl_handler(function () use ($checker) {
		throw new Exception;
	});
}

// Run
try {
	$fixerOk = $checker->runFixer();
	echo "\n\n";
	$snifferOk = $checker->runSniffer();
} catch (Throwable) {
	echo "Terminated\n";
	$checker->cleanup();
	exit(1);
}

$checker->cleanup();

if ($fixerOk && $snifferOk) {
	echo $dryRun ? "Code style checks passed.\n" : "Code style fixed successfully.\n";
	exit(0);
} else {
	echo $dryRun ? "Code style issues found.\n" : "Code style fixing failed or issues remain.\n";
	exit(1);
}
