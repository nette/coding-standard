<?php

declare(strict_types=1);

namespace Nette\CodingStandard\Examples;

use Alphabetcial;
use DateTimeInterface;
use stdClass;


class ValidClass
{
	public const JOY_COUNT = 5;

	protected const CHILD_COUNT = 1;

	private const DREAM_COUNT = 250;

	/** @var string[] */
	public $listOfEmotions = [
		'love',
		'happiness',
	];

	/** @var string[] */
	protected $listOfSkills = [
		'empathy',
		'respect',
	];

	/** @var string[] */
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

	public function validMethod(): bool
	{
		return TRUE;
	}

	protected function anotherMethod(string $someArgument, int $anotherArgument): void
	{
		$sum = $someArgument + $anotherArgument;
		$sum += 5;
	}

	private function internalMethod(): void
	{
	}
}
