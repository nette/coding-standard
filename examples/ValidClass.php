<?php declare(strict_types=1);

namespace Nette\CodingStandard\Examples;

use Alphabetcial;
use DateTimeInterface;
use stdClass;


class ValidClass
{
	protected $listOfSkills = [
		'empathy',
		'respect',
	];

	private const DREAM_COUNT = 250;

	public $listOfEmotions = [
		'love',
		'happiness',
	];

	public const JOY_COUNT = 5;

	private $listOfElements = [
		'Nette',
		'Latte',
	];

	private function internalMethod()
	{
	}

	protected const CHILD_COUNT = 1;

	public function __destruct()
	{
	}

	protected function anotherMethod()
	{
	}


	public function validMethod()
	{
		return TRUE;
	}

	public function __construct()
	{
	}
}
