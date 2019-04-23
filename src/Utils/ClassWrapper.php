<?php

declare(strict_types=1);

namespace Nette\CodingStandard\Utils;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use Symplify\CodingStandard\TokenRunner\Guard\TokenTypeGuard;

final class ClassWrapper
{
	/** @var int */
	private $startBracketIndex;

	/** @var int */
	private $endBracketIndex;

	/** @var TokensAnalyzer */
	private $tokensAnalyzer;

	/** @var Tokens */
	private $tokens;

	/** @var Token */
	private $classToken;

	/** @var int */
	private $startIndex;

	/** @var mixed[] */
	private $classyElements = [];


	private function __construct(Tokens $tokens, int $startIndex)
	{
		$this->classToken = $tokens[$startIndex];
		$this->startBracketIndex = $tokens->getNextTokenOfKind($startIndex, ['{']);
		$this->endBracketIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $this->startBracketIndex);

		$this->tokens = $tokens;
		$this->tokensAnalyzer = new TokensAnalyzer($tokens);
		$this->startIndex = $startIndex;
	}


	public static function createFromTokensArrayStartPosition(Tokens $tokens, int $startIndex): self
	{
		(new TokenTypeGuard)->ensureIsTokenType($tokens[$startIndex], [T_CLASS, T_INTERFACE, T_TRAIT], self::class);

		return new self($tokens, $startIndex);
	}


	public function getClassEnd(): int
	{
		return $this->endBracketIndex;
	}


	/**
	 * @return mixed[]
	 */
	public function getProperties(): array
	{
		return $this->filterClassyTokens($this->getClassyElements(), ['property']);
	}


	public function getLastPropertyPosition(): ?int
	{
		$properties = $this->getProperties();
		if ($properties === []) {
			return null;
		}

		end($properties);

		return key($properties);
	}


	public function getFirstMethodPosition(): ?int
	{
		$methods = $this->getMethods();
		if ($methods === []) {
			return null;
		}

		end($methods);

		return key($methods);
	}


	/**
	 * @return mixed[]
	 */
	public function getMethods(): array
	{
		return $this->filterClassyTokens($this->getClassyElements(), ['method']);
	}


	/**
	 * @param mixed[] $classyElements
	 * @param string[] $types
	 * @return mixed[]
	 */
	private function filterClassyTokens(array $classyElements, array $types): array
	{
		$filteredClassyTokens = [];

		foreach ($classyElements as $index => $classyToken) {
			if (!$this->isInClassRange($index)) {
				continue;
			}

			if (!in_array($classyToken['type'], $types, true)) {
				continue;
			}

			$filteredClassyTokens[$index] = $classyToken;
		}

		return $filteredClassyTokens;
	}


	private function isInClassRange(int $index): bool
	{
		if ($index < $this->startBracketIndex) {
			return false;
		}

		if ($index > $this->endBracketIndex) {
			return false;
		}

		return true;
	}


	/**
	 * @return mixed[]
	 */
	private function getClassyElements(): array
	{
		if ($this->classyElements) {
			return $this->classyElements;
		}

		return $this->classyElements = $this->tokensAnalyzer->getClassyElements();
	}
}
