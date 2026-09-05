<?php declare(strict_types=1);

/**
 * The ecs binary accepts the version 3 command line and runs DressCode with the Nette Coding Standard when no
 * configuration is around, leaving the decision to dresscode.neon when one exists.
 */

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';


function runEcs(string $cwd, string ...$args): array
{
	$cmd = array_merge([PHP_BINARY, __DIR__ . '/../ecs'], $args);
	$process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
	$stdout = (string) stream_get_contents($pipes[1]);
	$stderr = (string) stream_get_contents($pipes[2]);
	return [proc_close($process), $stdout, $stderr];
}


$project = sys_get_temp_dir() . '/ecs.bin.' . getmypid();
Helpers::purge($project);
$dirty = "<?php declare(strict_types=1);\n\n\$a = \"hello\" ;\n";
$clean = "<?php declare(strict_types=1);\n\n\$a = 'hello';\n";

test('check reports violations of the dresscode/nette preset', function () use ($project, $dirty) {
	file_put_contents("$project/sample.php", $dirty);
	[$exitCode, $stdout] = runEcs($project, 'check', 'sample.php');
	Assert::same(1, $exitCode);
	Assert::contains('single-quoted-strings', $stdout);
});

test('a version preset maps to the default', function () use ($project, $clean) {
	file_put_contents("$project/sample.php", $clean);
	[$exitCode, , $stderr] = runEcs($project, 'check', '--preset', 'php82', 'sample.php');
	Assert::same(0, $exitCode, $stderr);
});

test('the optimize-fn preset imports the optimized global functions', function () use ($project) {
	file_put_contents("$project/sample.php", "<?php declare(strict_types=1);\n\nnamespace App;\n\n\$a = count([]);\n");
	[$exitCode, , $stderr] = runEcs($project, 'fix', '--preset', 'optimize-fn', 'sample.php');
	Assert::same(0, $exitCode, $stderr);
	Assert::contains("\nuse function count;\n", (string) file_get_contents("$project/sample.php"));
});

test('fix and the --fix shortcut bring the file to the preset shape', function () use ($project, $dirty, $clean) {
	file_put_contents("$project/sample.php", $dirty);
	[$exitCode] = runEcs($project, 'fix', 'sample.php');
	Assert::same(0, $exitCode);
	Assert::same($clean, file_get_contents("$project/sample.php"));

	file_put_contents("$project/sample.php", $dirty);
	[$exitCode] = runEcs($project, '--fix', 'sample.php');
	Assert::same(0, $exitCode);
	Assert::same($clean, file_get_contents("$project/sample.php"));
});

test('without paths the src and tests directories are checked', function () use ($project, $dirty) {
	mkdir("$project/src");
	file_put_contents("$project/src/sample.php", $dirty);
	unlink("$project/sample.php");
	[$exitCode, $stdout] = runEcs($project, 'check');
	Assert::same(1, $exitCode);
	Assert::contains('sample.php', $stdout);
});

test('--config-file is refused with a pointer to dresscode.neon', function () use ($project) {
	[$exitCode, , $stderr] = runEcs($project, 'check', '--config-file', 'overrides.php', 'src');
	Assert::same(2, $exitCode);
	Assert::contains('dresscode.neon', $stderr);
});

test('a dresscode.neon wins over the default configuration', function () use ($project, $dirty) {
	file_put_contents("$project/src/sample.php", $dirty);
	file_put_contents("$project/dresscode.neon", "presets: []\n");
	[$exitCode, , $stderr] = runEcs($project, 'check', 'src/sample.php');
	Assert::same(0, $exitCode, $stderr);
	unlink("$project/dresscode.neon");
});
