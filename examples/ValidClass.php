<?php declare(strict_types=1);

namespace Nette\CodingStandard\Examples;

use Alphabetcial;
use DateTimeInterface;
use stdClass;


class ValidClass
{
	public const JOY_COUNT = 5;

	protected const CHILD_COUNT = 1;

	private const DREAM_COUNT = 250;

	public $listOfEmotions = [
		'love',
		'happiness',
	];

	protected $listOfSkills = [
		'empathy',
		'respect',
	];

	private $listOfElements = [
		'Nette',
		'Latte',
	];

	public function __construct()
	{
	}

	public function __destruct()
	{
	}

	public function validMethod()
	{
		return TRUE;
	}

	protected function anotherMethod(string $someArgument, int $anotherArgument)
	{
		$sum = $someArgument + $anotherArgument;
		$sum += 5;
	}

	private function internalMethod()
	{
	}
}
