<?php

/**
 * This file is part of the nothing
 *
 * @Nothing
 */

declare(strict_types=1);

namespace Nette\CodingStandard;

use Nette;


/**
 * @author nothing
 * @license MIT
 */
final class TestClass
{
	/**
	 * @return void
	 */
	public function hohoho(): void
	{
		echo 'ho' . ' ' . 'ho' . ' ' . 'ho';
	}

	/**
	 * @return void
	 */
	public function hahaha(): void
	{
		echo 'ha' . ' ' . 'ha' . ' ' . 'ha';
	}
}


$foo = new TestClass;

$foo->hohoho();
