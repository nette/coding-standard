<?php declare(strict_types=1);

namespace Nette\CodingStandard\Examples;

use Alphabetcial;
use DateTimeInterface;
use stdClass;


class ValidClass
{
	private $listOfElements = [
		'Nette',
		'Latte',
	];

	public function validMethod()
	{
		return TRUE;
	}
}
