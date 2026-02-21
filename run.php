<?php declare(strict_types=1);

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
$paths = [];
$preset = null;
$dryRun = true;

for ($i = 1; $i < $argc; $i++) {
	$arg = $argv[$i];
	if ($arg === '--preset' && isset($argv[$i + 1])) {
		$preset = $argv[++$i];
	} elseif ($arg === '--fix' || $arg === 'fix') {
		$dryRun = false;
	} elseif ($arg === 'check') {
		$dryRun = true;
	} elseif ($arg === '--help' || $arg === '-h') {
		echo "Usage: php run.php [check|fix] [--preset <name>] [path1 path2 ...]\n";
		echo "  check (default): Run tools in dry-run mode.\n";
		echo "  fix: Run tools and apply fixes.\n";
		echo "  --preset <name>: Specify preset (e.g., php81). Autodetected if omitted.\n";
		echo "  --version, -V: Show version information.\n";
		echo "  path1 path2 ...: Specific files or directories to process. Defaults to src/, tests/ or ./\n";
		exit(0);
	} elseif ($arg === '--version' || $arg === '-V') {
		echo 'Nette Coding Standard ' . VERSION . "\n";
		exit(0);
	} elseif (!str_starts_with($arg, '-')) {
		$paths[] = $arg;
	} else {
		fwrite(STDERR, "Warning: Ignoring unknown option '{$arg}'\n");
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
$checker = new Checker($vendorDir, $root, $dryRun, $preset);
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
} catch (\Throwable) {
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
