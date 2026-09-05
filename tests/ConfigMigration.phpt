<?php declare(strict_types=1);

/**
 * ecs migrate turns ncs.php and ncs.xml of version 3 into dresscode.neon.
 */

use Nette\CodingStandard\ConfigMigration;
use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';


$project = sys_get_temp_dir() . '/ncs.migration.' . getmypid();
Helpers::purge($project);


test('fixer overrides, sniff exclusions and file exclusions', function () use ($project) {
	@mkdir("$project/src");
	file_put_contents("$project/ncs.php", <<<'XX'
		<?php
		return [
			'ordered_class_elements' => false,
			'PhpCsFixerCustomFixers/commented_out_function' => false,
			'Nette/statement_indentation' => true,
			'no_extra_blank_lines' => ['tokens' => ['use']],
			'unknown_fixer' => false,
		];
		XX);
	file_put_contents("$project/ncs.xml", <<<'XX'
		<?xml version="1.0"?>
		<ruleset name="Custom" namespace="Nette">
			<exclude-pattern>./tests/Utils/Reflection.getDeclaringMethod.alias.phpt</exclude-pattern>
			<rule ref="SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly">
				<exclude-pattern>./tests/*/*.phpt</exclude-pattern>
			</rule>
			<rule ref="Generic.PHP.DeprecatedFunctions.Deprecated">
				<exclude-pattern>Strings.php</exclude-pattern>
				<exclude-pattern>.\src\Tracy\Helpers.php</exclude-pattern>
			</rule>
			<rule ref="Squiz.PHP.NonExecutableCode.Unreachable">
				<exclude-pattern>*.phpt</exclude-pattern>
			</rule>
			<rule ref="SlevomatCodingStandard.PHP.RequireNowdoc"/>
		</ruleset>
		XX);
	$migration = new ConfigMigration;
	Assert::same(str_replace("\r\n", "\n", <<<'XX'
		extensions:
			- Nette\CodingStandard\Extension

		presets:
			- dresscode/nette

		paths:
			- src

		rules:
			dresscode/ordered-members: false
			dresscode/commented-out-function: false
			dresscode/indentation: true
			dresscode/header-blank-lines:
				tokens:
					- use

		excludePaths:
			- tests/Utils/Reflection.getDeclaringMethod.alias.phpt

		excludeRulePaths:
			dresscode/reference-throwable-only:
				- tests/*/*.phpt

			dresscode/no-deprecated-functions:
				- Strings.php
				- src/Tracy/Helpers.php

		XX), $migration->migrate($project));
	Assert::same(['unknown_fixer', 'Squiz.PHP.NonExecutableCode.Unreachable'], $migration->unknown);
	Assert::same([
		'dresscode/header-blank-lines: the options of no_extra_blank_lines are copied as they are; check their names against `dresscode rules`',
		'SlevomatCodingStandard.PHP.RequireNowdoc: a rule element without exclude-pattern has no counterpart and was left out',
	], $migration->notes);
});


test('nothing to migrate', function () use ($project) {
	unlink("$project/ncs.php");
	unlink("$project/ncs.xml");
	Assert::null((new ConfigMigration)->migrate($project));
});


test('ecs migrate writes dresscode.neon and refuses to overwrite it', function () use ($project) {
	file_put_contents("$project/ncs.php", "<?php\nreturn ['ordered_class_elements' => false];\n");
	$run = function () use ($project): array {
		$process = proc_open([PHP_BINARY, __DIR__ . '/../ecs', 'migrate'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $project);
		$stdout = (string) stream_get_contents($pipes[1]);
		$stderr = (string) stream_get_contents($pipes[2]);
		return [proc_close($process), $stdout, $stderr];
	};
	[$code, $stdout] = $run();
	Assert::same(0, $code);
	Assert::match("dresscode.neon written; delete ncs.php and ncs.xml once it works.\n", $stdout);
	Assert::contains("\tdresscode/ordered-members: false\n", (string) file_get_contents("$project/dresscode.neon"));
	[$code, , $stderr] = $run();
	Assert::same(2, $code);
	Assert::match('%A%dresscode.neon already exists%A%', $stderr);
});
