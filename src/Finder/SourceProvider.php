<?php

namespace Nette\CodingStandard\Finder;

use Nette\Utils\Finder;
use Symplify\EasyCodingStandard\Contract\Finder\CustomSourceProviderInterface;


final class SourceProvider implements CustomSourceProviderInterface
{
	/**
	 * @param string[] $source
	 * @return \SplFileInfo[]
	 */
	public function find(array $source): array
	{
		$finder = Finder::findFiles([
				'*.php',
				'*.phpt',
			])
			->from($source)
			->exclude('expected')
			->exclude('fixtures')
			->exclude('fixtures*')
			->exclude('output')
			->exclude('vendor')
			->exclude('tmp');

		return iterator_to_array($finder);
	}
}
