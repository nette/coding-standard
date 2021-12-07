<?php

declare(strict_types=1);

if (
	!(is_file($file = ($vendorDir = __DIR__ . '/vendor') . '/autoload.php') && include $file) &&
	!(is_file($file = ($vendorDir = __DIR__ . '/../..') . '/autoload.php') && include $file)
) {
	fwrite(STDERR, "Install packages using Composer.\n");
	exit(1);
}

if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGINT, function (): void {
		pcntl_signal(SIGINT, SIG_DFL);
		echo "Terminated\n";
		exit(1);
	});
} elseif (function_exists('sapi_windows_set_ctrl_handler')) {
	sapi_windows_set_ctrl_handler(function () {
		echo "Terminated\n";
		exit(1);
	});
}

set_time_limit(0);


// parse arguments
$paths = [];
$preset = null;
$dry = true;

for ($i = 1; $i < count($argv); $i++) {
	$arg = $argv[$i];
	if ($arg === '--preset') {
		$preset = $argv[++$i];
	} elseif ($arg === '--fix' || $arg === 'fix') {
		$dry = false;
	} elseif ($arg === 'check') {
		// ignore
	} else {
		$paths[] = $arg;
	}
}


// try to find out the PHP version from the composer.json
$presetVersions = ['8.1', '8.0', '7.4', '7.3', '7.1'];


$root = getcwd();
while (!is_file("$root/composer.json") && substr_count($root, DIRECTORY_SEPARATOR) > 1) {
	$root = dirname($root);
}
if (is_file("$root/composer.json")) {
	$json = @json_decode(file_get_contents("$root/composer.json"));
	if (preg_match('#(\d+\.\d+)#', $json->require->php ?? '', $m)) {
		$requiredPhpVersion = $m[1];
	}
}
$preset = $preset ?? ('php' . str_replace('.', '', $requiredPhpVersion ?? end($presetVersions)));

echo "Preset: $preset\n";


// build file list
$paths = $paths ?: array_filter([
	'src',
	'tests',
], 'is_dir') ?: ['.'];

$finder = PhpCsFixer\Finder::create()
	->name(['*.php', '*.phpt'])
	->notPath([
		'/fixtures.*/',
		'temp',
		'tmp',
		'vendor',
	])
	->filter(function (SplFileInfo $file) {
		$contents = file_get_contents((string) $file);
		return !preg_match('#@phpVersion\s+([0-9.]+)#i', $contents, $m)
			|| version_compare(PHP_VERSION, $m[1], '>=');
	})
	->in($paths);

$fileList = __DIR__ . '/filelist.tmp';
file_put_contents($fileList, implode("\n", iterator_to_array($finder)));


// PHP CS Fixer
passthru(
	'php ' . escapeshellarg($vendorDir . '/friendsofphp/php-cs-fixer/php-cs-fixer')
	. ' fix -v'
	. ($dry ? ' --dry-run' : '')
	. ' --config ' . escapeshellarg(__DIR__ . "/preset-fixer/$preset.php"),
	$code
);
$ok = !$code;


// exit
exit($ok ? 0 : 1);
