<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();


/**
 * Returns the CLI binary. Tester itself may run under the CGI SAPI, which lacks the STDERR
 * constant the spawned tools rely on.
 */
function phpCliBinary(): string
{
	if (PHP_SAPI === 'cli') {
		return PHP_BINARY;
	}

	$path = dirname(PHP_BINARY) . '/php' . (DIRECTORY_SEPARATOR === '\\' ? '.exe' : '');
	return is_file($path) ? $path : PHP_BINARY;
}
