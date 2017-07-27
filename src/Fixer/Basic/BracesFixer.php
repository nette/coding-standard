<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Nette\CodingStandard\Fixer\Basic;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * Fixer for rules defined in PSR2 ¶4.1, ¶4.4, ¶5.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class BracesFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
	/**
	 * @internal
	 */
	private const LINE_NEXT = 'next';

	/**
	 * @internal
	 */
	private const LINE_SAME = 'same';


	/**
	 * {@inheritdoc}
	 */
	public function getDefinition()
	{
		return new FixerDefinition(
			'The body of each structure MUST be enclosed by braces. Braces should be properly placed. Body of braces should be properly indented.',
			[
				new CodeSample(
'<?php

class Foo {
	public function bar($baz) {
		if ($baz = 900) echo "Hello!";

		if ($baz = 9000)
			echo "Wait!";

		if ($baz == true)
		{
			echo "Why?";
		}
		else
		{
			echo "Ha?";
		}

		if (is_array($baz))
			foreach ($baz as $b)
			{
				echo $b;
			}
	}
}
'
				),
				new CodeSample(
'<?php
$positive = function ($item) { return $item >= 0; };
$negative = function ($item) {
				return $item < 0; };
',
					['allow_single_line_closure' => true]
				),
				new CodeSample(
'<?php

class Foo
{
	public function bar($baz)
	{
		if ($baz = 900) echo "Hello!";

		if ($baz = 9000)
			echo "Wait!";

		if ($baz == true)
		{
			echo "Why?";
		}
		else
		{
			echo "Ha?";
		}

		if (is_array($baz))
			foreach ($baz as $b)
			{
				echo $b;
			}
	}
}
',
					['position_after_functions_and_oop_constructs' => self::LINE_SAME]
				),
			]
		);
	}


	/**
	 * {@inheritdoc}
	 */
	public function getPriority()
	{
		// should be run after the ElseIfFixer, NoEmptyStatementFixer and NoUselessElseFixer
		return -25;
	}


	/**
	 * {@inheritdoc}
	 */
	public function isCandidate(Tokens $tokens)
	{
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	protected function applyFix(\SplFileInfo $file, Tokens $tokens)
	{
		$this->fixCommentBeforeBrace($tokens);
		$this->fixMissingControlBraces($tokens);
		$this->fixIndents($tokens);
		$this->fixControlContinuationBraces($tokens);
		$this->fixSpaceAroundToken($tokens);
		$this->fixDoWhile($tokens);
	}


	/**
	 * {@inheritdoc}
	 */
	protected function createConfigurationDefinition()
	{
		return new FixerConfigurationResolver([
			(new FixerOptionBuilder('allow_single_line_closure', 'Whether single line lambda notation should be allowed.'))
				->setAllowedTypes(['bool'])
				->setDefault(false)
				->getOption(),
			(new FixerOptionBuilder('position_after_functions_and_oop_constructs', 'whether the opening brace should be placed on "next" or "same" line after classy constructs (non-anonymous classes, interfaces, traits, methods and non-lambda functions).'))
				->setAllowedValues([self::LINE_NEXT, self::LINE_SAME])
				->setDefault(self::LINE_NEXT)
				->getOption(),
		]);
	}


	private function fixCommentBeforeBrace(Tokens $tokens)
	{
		$tokensAnalyzer = new TokensAnalyzer($tokens);
		$controlTokens = $this->getControlTokens();

		for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
			$token = $tokens[$index];

			if ($token->isGivenKind($controlTokens)) {
				$prevIndex = $this->findParenthesisEnd($tokens, $index);
			} elseif (
				($token->isGivenKind(T_FUNCTION) && $tokensAnalyzer->isLambda($index)) ||
				($token->isGivenKind(T_CLASS) && $tokensAnalyzer->isAnonymousClass($index))
			) {
				$prevIndex = $tokens->getNextTokenOfKind($index, ['{']);
				$prevIndex = $tokens->getPrevMeaningfulToken($prevIndex);
			} else {
				continue;
			}

			$commentIndex = $tokens->getNextNonWhitespace($prevIndex);
			$commentToken = $tokens[$commentIndex];

			if (!$commentToken->isGivenKind(T_COMMENT) || strpos($commentToken->getContent(), '/*') === 0) {
				continue;
			}

			$braceIndex = $tokens->getNextMeaningfulToken($commentIndex);
			$braceToken = $tokens[$braceIndex];

			if (!$braceToken->equals('{')) {
				continue;
			}

			$tokenTmp = $tokens[$braceIndex];

			$newBraceIndex = $prevIndex + 1;
			for ($i = $braceIndex; $i > $newBraceIndex; --$i) {
				// we might be moving one white space next to another, these have to be merged
				$tokens[$i] = $tokens[$i - 1];
				if ($tokens[$i]->isWhitespace() && $tokens[$i + 1]->isWhitespace()) {
					$tokens[$i]->setContent($tokens[$i]->getContent() . $tokens[$i + 1]->getContent());
					$tokens[$i + 1]->clear();
				}
			}

			$tokens[$newBraceIndex] = $tokenTmp;
			$c = $tokens[$braceIndex]->getContent();
			if (substr_count($c, "\n") > 1) {
				// left trim till last line break
				$tokens[$braceIndex]->setContent(substr($c, strrpos($c, "\n")));
			}
		}
	}


	private function fixControlContinuationBraces(Tokens $tokens)
	{
		$controlContinuationTokens = $this->getControlContinuationTokens();

		for ($index = count($tokens) - 1; 0 <= $index; --$index) {
			$token = $tokens[$index];

			if (!$token->isGivenKind($controlContinuationTokens)) {
				continue;
			}

			$prevIndex = $tokens->getPrevNonWhitespace($index);
			$prevToken = $tokens[$prevIndex];

			if (!$prevToken->equals('}')) {
				continue;
			}

			$tokens->ensureWhitespaceAtIndex($index - 1, 1, ' ');
		}
	}


	private function fixDoWhile(Tokens $tokens)
	{
		for ($index = count($tokens) - 1; 0 <= $index; --$index) {
			$token = $tokens[$index];

			if (!$token->isGivenKind(T_DO)) {
				continue;
			}

			$parenthesisEndIndex = $this->findParenthesisEnd($tokens, $index);
			$startBraceIndex = $tokens->getNextNonWhitespace($parenthesisEndIndex);
			$endBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startBraceIndex);
			$nextNonWhitespaceIndex = $tokens->getNextNonWhitespace($endBraceIndex);
			$nextNonWhitespaceToken = $tokens[$nextNonWhitespaceIndex];

			if (!$nextNonWhitespaceToken->isGivenKind(T_WHILE)) {
				continue;
			}

			$tokens->ensureWhitespaceAtIndex($nextNonWhitespaceIndex - 1, 1, ' ');
		}
	}


	private function fixIndents(Tokens $tokens)
	{
		$classyTokens = Token::getClassyTokenKinds();
		$classyAndFunctionTokens = array_merge([T_FUNCTION], $classyTokens);
		$controlTokens = $this->getControlTokens();
		$indentTokens = array_filter(
			array_merge($classyAndFunctionTokens, $controlTokens),
			function ($item) {
				return $item !== T_SWITCH;
			}
		);
		$tokensAnalyzer = new TokensAnalyzer($tokens);

		for ($index = 0, $limit = count($tokens); $index < $limit; ++$index) {
			$token = $tokens[$index];

			// if token is not a structure element - continue
			if (!$token->isGivenKind($indentTokens)) {
				continue;
			}

			// do not change indent for `while` in `do ... while ...`
			if (
				$token->isGivenKind(T_WHILE)
				&& $tokensAnalyzer->isWhilePartOfDoWhile($index)
			) {
				continue;
			}

			if (
				$this->configuration['allow_single_line_closure']
				&& $token->isGivenKind(T_FUNCTION)
				&& $tokensAnalyzer->isLambda($index)
			) {
				$braceEndIndex = $tokens->findBlockEnd(
					Tokens::BLOCK_TYPE_CURLY_BRACE,
					$tokens->getNextTokenOfKind($index, ['{'])
				);

				if (!$this->isMultilined($tokens, $index, $braceEndIndex)) {
					$index = $braceEndIndex;

					continue;
				}
			}

			if ($token->isGivenKind($classyAndFunctionTokens)) {
				$startBraceIndex = $tokens->getNextTokenOfKind($index, [';', '{']);
				$startBraceToken = $tokens[$startBraceIndex];
			} else {
				$parenthesisEndIndex = $this->findParenthesisEnd($tokens, $index);
				$startBraceIndex = $tokens->getNextNonWhitespace($parenthesisEndIndex);
				$startBraceToken = $tokens[$startBraceIndex];
			}

			// structure without braces block - nothing to do, e.g. do { } while (true);
			if (!$startBraceToken->equals('{')) {
				continue;
			}

			$endBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startBraceIndex);

			$indent = $this->detectIndent($tokens, $index);

			// fix indent near closing brace
			$endBraceNextNonWhitespaceIndex = $tokens->getNextNonWhitespace($endBraceIndex);
			if ($endBraceNextNonWhitespaceIndex === null || !$tokens[$endBraceNextNonWhitespaceIndex]->isGivenKind([T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY])) {
				$tokens->ensureWhitespaceAtIndex($endBraceIndex - 1, 1, $this->whitespacesConfig->getLineEnding() . $indent);
			}

			// fix indent between braces
			$lastCommaIndex = $tokens->getPrevTokenOfKind($endBraceIndex - 1, [';', '}']);

			$nestLevel = 1;
			for ($nestIndex = $lastCommaIndex; $nestIndex >= $startBraceIndex; --$nestIndex) {
				$nestToken = $tokens[$nestIndex];

				if ($nestToken->equals(')')) {
					$nestIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $nestIndex, false);
					continue;
				}

				if ($nestLevel === 1 && $nestToken->equalsAny([';', '}'])) {
					$nextNonWhitespaceNestIndex = $tokens->getNextNonWhitespace($nestIndex);
					$nextNonWhitespaceNestToken = $tokens[$nextNonWhitespaceNestIndex];

					if (
						// next Token is not a comment
						!$nextNonWhitespaceNestToken->isComment() &&
						// and it is not a `$foo = function () {};` situation
						!($nestToken->equals('}') && $nextNonWhitespaceNestToken->equalsAny([';', ',', ']', [CT::T_ARRAY_SQUARE_BRACE_CLOSE]])) &&
						// and it is not a `Foo::{bar}()` situation
						!($nestToken->equals('}') && $nextNonWhitespaceNestToken->equals('(')) &&
						// and it is not a `${"a"}->...` and `${"b{$foo}"}->...` situation
						!($nestToken->equals('}') && $tokens[$nestIndex - 1]->equalsAny(['"', "'", [T_CONSTANT_ENCAPSED_STRING]]))
					) {
						if (
							$nextNonWhitespaceNestToken->isGivenKind($this->getControlContinuationTokens())
							|| $nextNonWhitespaceNestToken->isGivenKind(T_CLOSE_TAG)
							|| (
								$nextNonWhitespaceNestToken->isGivenKind(T_WHILE) &&
								$tokensAnalyzer->isWhilePartOfDoWhile($nextNonWhitespaceNestIndex)
							)
						) {
							$whitespace = ' ';
						} else {
							$nextToken = $tokens[$nestIndex + 1];
							$nextWhitespace = '';

							if ($nextToken->isWhitespace()) {
								$nextWhitespace = rtrim($nextToken->getContent(), " \t");

								if ($nextWhitespace !== '') {
									$nextWhitespace = preg_replace(
										sprintf('/%s$/', $this->whitespacesConfig->getLineEnding()),
										'',
										$nextWhitespace,
										1
									);
								}
							}

							$whitespace = $nextWhitespace . $this->whitespacesConfig->getLineEnding() . $indent;

							if (!$nextNonWhitespaceNestToken->equals('}')) {
								$whitespace .= $this->whitespacesConfig->getIndent();
							}
						}

						$tokens->ensureWhitespaceAtIndex($nestIndex + 1, 0, $whitespace);
					}
				}

				if ($nestToken->equals('}')) {
					++$nestLevel;
					continue;
				}

				if ($nestToken->equals('{')) {
					--$nestLevel;
					continue;
				}
			}

			// fix indent near opening brace
			if (isset($tokens[$startBraceIndex + 2]) && $tokens[$startBraceIndex + 2]->equals('}')) {
				$tokens->ensureWhitespaceAtIndex($startBraceIndex + 1, 0, $this->whitespacesConfig->getLineEnding() . $indent);
			} else {
				$nextToken = $tokens[$startBraceIndex + 1];
				$nextNonWhitespaceToken = $tokens[$tokens->getNextNonWhitespace($startBraceIndex)];

				// set indent only if it is not a case, when comment is following { in same line
				if (
					!$nextNonWhitespaceToken->isComment()
					|| !($nextToken->isWhitespace() && $nextToken->isWhitespace(" \t"))
					&& substr_count($nextToken->getContent(), "\n") === 1 // preserve blank lines
				) {
					$tokens->ensureWhitespaceAtIndex($startBraceIndex + 1, 0, $this->whitespacesConfig->getLineEnding() . $indent . $this->whitespacesConfig->getIndent());
				}
			}

			if ($token->isGivenKind($classyTokens) && !$tokensAnalyzer->isAnonymousClass($index)) {
				if ($this->configuration['position_after_functions_and_oop_constructs'] === self::LINE_SAME && !$tokens[$tokens->getPrevNonWhitespace($startBraceIndex)]->isComment()) {
					$ensuredWhitespace = ' ';
				} else {
					$ensuredWhitespace = $this->whitespacesConfig->getLineEnding() . $indent;
				}

				$tokens->ensureWhitespaceAtIndex($startBraceIndex - 1, 1, $ensuredWhitespace);
			} elseif ($token->isGivenKind(T_FUNCTION) && !$tokensAnalyzer->isLambda($index)) {
				$closingParenthesisIndex = $tokens->getPrevTokenOfKind($startBraceIndex, [')']);
				if ($closingParenthesisIndex === null) {
					continue;
				}

				$prevToken = $tokens[$closingParenthesisIndex - 1];
				if ($prevToken->isWhitespace() && strpos($prevToken->getContent(), "\n") !== false) {
					if (!$tokens[$startBraceIndex - 2]->isComment()) {
						$tokens->ensureWhitespaceAtIndex($startBraceIndex - 1, 1, ' ');
					}
				} else {
					if ($this->configuration['position_after_functions_and_oop_constructs'] === self::LINE_SAME && !$tokens[$tokens->getPrevNonWhitespace($startBraceIndex)]->isComment()) {
						$ensuredWhitespace = ' ';
					} else {
						$ensuredWhitespace = $this->whitespacesConfig->getLineEnding() . $indent;
					}

					$tokens->ensureWhitespaceAtIndex($startBraceIndex - 1, 1, $ensuredWhitespace);
				}
			} else {
				$tokens->ensureWhitespaceAtIndex($startBraceIndex - 1, 1, ' ');
			}

			// reset loop limit due to collection change
			$limit = count($tokens);
		}
	}


	private function fixMissingControlBraces(Tokens $tokens)
	{
		$controlTokens = $this->getControlTokens();

		for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
			$token = $tokens[$index];

			if (!$token->isGivenKind($controlTokens)) {
				continue;
			}

			$parenthesisEndIndex = $this->findParenthesisEnd($tokens, $index);
			$tokenAfterParenthesis = $tokens[$tokens->getNextMeaningfulToken($parenthesisEndIndex)];

			// if Token after parenthesis is { then we do not need to insert brace, but to fix whitespace before it
			if ($tokenAfterParenthesis->equals('{')) {
				$tokens->ensureWhitespaceAtIndex($parenthesisEndIndex + 1, 0, ' ');

				continue;
			}

			// do not add braces for cases:
			// - structure without block, e.g. while ($iter->next());
			// - structure with block, e.g. while ($i) {...}, while ($i) : {...} endwhile;
			if ($tokenAfterParenthesis->equalsAny([';', '{', ':'])) {
				continue;
			}

			$statementEndIndex = $this->findStatementEnd($tokens, $parenthesisEndIndex);

			// insert closing brace
			$tokens->insertAt($statementEndIndex + 1, [new Token([T_WHITESPACE, ' ']), new Token('}')]);

			// insert missing `;` if needed
			if (!$tokens[$statementEndIndex]->equalsAny([';', '}'])) {
				$tokens->insertAt($statementEndIndex + 1, new Token(';'));
			}

			// insert opening brace
			$tokens->insertAt($parenthesisEndIndex + 1, new Token('{'));
			$tokens->ensureWhitespaceAtIndex($parenthesisEndIndex + 1, 0, ' ');
		}
	}


	private function fixSpaceAroundToken(Tokens $tokens)
	{
		$controlTokens = $this->getControlTokens();

		for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
			$token = $tokens[$index];

			// Declare tokens don't follow the same rules are other control statements
			if ($token->isGivenKind(T_DECLARE)) {
				$this->fixDeclareStatement($tokens, $index);
			} elseif ($token->isGivenKind($controlTokens) || $token->isGivenKind(CT::T_USE_LAMBDA)) {
				$nextNonWhitespaceIndex = $tokens->getNextNonWhitespace($index);

				if (!$tokens[$nextNonWhitespaceIndex]->equals(':')) {
					$tokens->ensureWhitespaceAtIndex($index + 1, 0, ' ');
				}

				$prevToken = $tokens[$index - 1];

				if (!$prevToken->isWhitespace() && !$prevToken->isComment() && !$prevToken->isGivenKind(T_OPEN_TAG)) {
					$tokens->ensureWhitespaceAtIndex($index - 1, 1, ' ');
				}
			}
		}
	}


	/**
	 * @param Tokens $tokens
	 * @param int    $index
	 *
	 * @return string
	 */
	private function detectIndent(Tokens $tokens, $index)
	{
		while (true) {
			$whitespaceIndex = $tokens->getPrevTokenOfKind($index, [[T_WHITESPACE]]);

			if ($whitespaceIndex === null) {
				return '';
			}

			$whitespaceToken = $tokens[$whitespaceIndex];

			if (strpos($whitespaceToken->getContent(), "\n") !== false) {
				break;
			}

			$prevToken = $tokens[$whitespaceIndex - 1];

			if ($prevToken->isGivenKind([T_OPEN_TAG, T_COMMENT]) && substr($prevToken->getContent(), -1) === "\n") {
				break;
			}

			$index = $whitespaceIndex;
		}

		$explodedContent = explode("\n", $whitespaceToken->getContent());

		return end($explodedContent);
	}


	/**
	 * @param Tokens $tokens
	 * @param int    $structureTokenIndex
	 *
	 * @return int
	 */
	private function findParenthesisEnd(Tokens $tokens, $structureTokenIndex)
	{
		$nextIndex = $tokens->getNextMeaningfulToken($structureTokenIndex);
		$nextToken = $tokens[$nextIndex];

		// return if next token is not opening parenthesis
		if (!$nextToken->equals('(')) {
			return $structureTokenIndex;
		}

		return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $nextIndex);
	}


	private function findStatementEnd(Tokens $tokens, $parenthesisEndIndex)
	{
		$nextIndex = $tokens->getNextMeaningfulToken($parenthesisEndIndex);
		$nextToken = $tokens[$nextIndex];

		if (!$nextToken) {
			return $parenthesisEndIndex;
		}

		if ($nextToken->equals('{')) {
			return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $nextIndex);
		}

		if ($nextToken->isGivenKind($this->getControlTokens())) {
			$parenthesisEndIndex = $this->findParenthesisEnd($tokens, $nextIndex);

			$endIndex = $this->findStatementEnd($tokens, $parenthesisEndIndex);

			if ($nextToken->isGivenKind([T_IF, T_TRY, T_DO])) {
				$openingTokenKind = $nextToken->getId();

				while (true) {
					$nextIndex = $tokens->getNextMeaningfulToken($endIndex);
					$nextToken = isset($nextIndex) ? $tokens[$nextIndex] : null;
					if ($nextToken && $nextToken->isGivenKind($this->getControlContinuationTokensForOpeningToken($openingTokenKind))) {
						$parenthesisEndIndex = $this->findParenthesisEnd($tokens, $nextIndex);

						$endIndex = $this->findStatementEnd($tokens, $parenthesisEndIndex);

						if ($nextToken->isGivenKind($this->getFinalControlContinuationTokensForOpeningToken($openingTokenKind))) {
							return $endIndex;
						}
					} else {
						break;
					}
				}
			}

			return $endIndex;
		}

		$index = $parenthesisEndIndex;

		while (true) {
			$token = $tokens[++$index];

			// if there is some block in statement (eg lambda function) we need to skip it
			if ($token->equals('{')) {
				$index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
				continue;
			}

			if ($token->equals(';')) {
				return $index;
			}

			if ($token->isGivenKind(T_CLOSE_TAG)) {
				return $tokens->getPrevNonWhitespace($index);
			}
		}

		throw new \RuntimeException('Statement end not found.');
	}


	private function getControlTokens()
	{
		static $tokens = [
			T_DECLARE,
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_FINALLY,
			T_FOR,
			T_FOREACH,
			T_IF,
			T_WHILE,
			T_TRY,
			T_CATCH,
			T_SWITCH,
		];

		return $tokens;
	}


	private function getControlContinuationTokens()
	{
		static $tokens = [
			T_CATCH,
			T_ELSE,
			T_ELSEIF,
			T_FINALLY,
		];

		return $tokens;
	}


	private function getControlContinuationTokensForOpeningToken($openingTokenKind)
	{
		if ($openingTokenKind === T_IF) {
			return [
				T_ELSE,
				T_ELSEIF,
			];
		}

		if ($openingTokenKind === T_DO) {
			return [T_WHILE];
		}

		if ($openingTokenKind === T_TRY) {
			return [
				T_CATCH,
				T_FINALLY,
			];
		}

		return [];
	}


	private function getFinalControlContinuationTokensForOpeningToken($openingTokenKind)
	{
		if ($openingTokenKind === T_IF) {
			return [T_ELSE];
		}

		if ($openingTokenKind === T_TRY) {
			return [T_FINALLY];
		}

		return [];
	}


	/**
	 * @param Tokens $tokens
	 * @param int    $index
	 */
	private function fixDeclareStatement(Tokens $tokens, $index)
	{
		$tokens->removeTrailingWhitespace($index);

		$startParenthesisIndex = $tokens->getNextTokenOfKind($index, ['(']);
		$tokens->removeTrailingWhitespace($startParenthesisIndex);

		$endParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startParenthesisIndex);
		$tokens->removeLeadingWhitespace($endParenthesisIndex);

		$startBraceIndex = $tokens->getNextTokenOfKind($endParenthesisIndex, [';', '{']);
		$startBraceToken = $tokens[$startBraceIndex];

		if ($startBraceToken->equals('{')) {
			$this->fixSingleLineWhitespaceForDeclare($tokens, $startBraceIndex);
		}
	}


	/**
	 * @param Tokens $tokens
	 * @param int    $startBraceIndex
	 */
	private function fixSingleLineWhitespaceForDeclare(Tokens $tokens, $startBraceIndex)
	{
		// fix single-line whitespace before {
		// eg: `declare(ticks=1){` => `declare(ticks=1) {`
		// eg: `declare(ticks=1)   {` => `declare(ticks=1) {`
		if (
			!$tokens[$startBraceIndex - 1]->isWhitespace() ||
			$tokens[$startBraceIndex - 1]->isWhitespace(" \t")
		) {
			$tokens->ensureWhitespaceAtIndex($startBraceIndex - 1, 1, ' ');
		}
	}


	/**
	 * @param Tokens $tokens
	 * @param int    $startParenthesisIndex
	 * @param int    $endParenthesisIndex
	 *
	 * @return bool
	 */
	private function isMultilined(Tokens $tokens, $startParenthesisIndex, $endParenthesisIndex)
	{
		for ($i = $startParenthesisIndex; $i < $endParenthesisIndex; ++$i) {
			if (strpos($tokens[$i]->getContent(), "\n") !== false) {
				return true;
			}
		}

		return false;
	}
}
