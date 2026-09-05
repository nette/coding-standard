<?php declare(strict_types=1);

namespace Nette\CodingStandard;


/**
 * Skips files that declare a required PHP version above the running one: a file with
 * `@phpVersion X.Y` in its content is left out when running on PHP < X.Y.
 * The extension wires it in; on its own: `Config::create()->skipWhen(PhpVersionFilter::create())`.
 */
final class PhpVersionFilter
{
	/** @return \Closure(string, string): bool */
	public static function create(): \Closure
	{
		return fn(string $content, string $path): bool => preg_match('~@phpVersion\s+([0-9.]+)~i', $content, $m) === 1
			&& version_compare(PHP_VERSION, $m[1], '<');
	}
}
