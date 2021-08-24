<?php

declare(strict_types=1);

namespace Nette\CodingStandard\Examples;


class ValidClass
{
	protected const CHILD_COUNT = 1,
		HOUSE_COUNT = 10; // allow comment

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


	#[Attribute]
	public function validMethod()
	{
		return true;
	}


	/** doc */
	#[Attribute]
	protected function anotherMethod($someArgument, $anotherArgument)
	{
		$sum = $someArgument + $anotherArgument;
		$sum += 5;
	}


	#[Attribute]
	/** doc */
	private function internalMethod()
	{
	}
}
