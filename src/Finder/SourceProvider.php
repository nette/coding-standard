<?php

declare(strict_types=1);

namespace Nette\CodingStandard\Finder;

use IteratorAggregate;
use Nette\Utils\Finder;
use Symplify\EasyCodingStandard\Contract\Finder\CustomSourceProviderInterface;


final class SourceProvider implements CustomSourceProviderInterface
{
	/**
	 * @param string[] $source
	 */
	public function find(array $source): IteratorAggregate
	{
		return Finder::findFiles([
				'*.php',
				'*.phpt',
			])
			->from($source)
			->exclude('expected')
			->exclude('fixtures')
			->exclude('fixtures*')
			->exclude('output')
			->exclude('vendor')
			->exclude('temp')
			->exclude('tmp');
	}
}
