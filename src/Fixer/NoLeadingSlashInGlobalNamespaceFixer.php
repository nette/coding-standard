<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer: custom fixers.
 *
 * (c) 2018 Kuba Werłos
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// PhpCsFixerCustomFixers\Fixer;

namespace NetteCodingStandard\Fixer\Import;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;


/**
 * Unlike the upstream fixer, the leading slash is kept when the first segment of the name
 * is shadowed by an import, because there `\Foo\Bar` and `Foo\Bar` are different classes.
 */
final class NoLeadingSlashInGlobalNamespaceFixer extends AbstractFixer
{
	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'Classes in the global namespace cannot contain leading slashes.',
			[new CodeSample("<?php\nuse Foo\\Foo;\n\n\$a = new \\Foo\\Bar;\n\$b = new \\Baz;\n")],
		);
	}


	public function getPriority(): int
	{
		return 0;
	}


	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isTokenKindFound(T_NS_SEPARATOR);
	}


	public function isRisky(): bool
	{
		return false;
	}


	public function getName(): string
	{
		return 'Nette/' . parent::getName();
	}


	protected function applyFix(SplFileInfo $file, Tokens $tokens): void
	{
		$aliases = self::getImportedClassAliases($tokens);
		$index = 0;
		while (++$index < $tokens->count()) {
			$index = self::skipNamespacedCode($tokens, $index);

			if (!self::isToRemove($tokens, $index) || self::isShadowedByImport($tokens, $index, $aliases)) {
				continue;
			}

			$tokens->clearTokenAndMergeSurroundingWhitespace($index);
		}
	}


	/**
	 * Lowercased short names of all classes imported in the file. Imports from a namespaced
	 * block are collected too; that only makes the fixer skip more, never remove more.
	 * @return array<string, true>
	 */
	private static function getImportedClassAliases(Tokens $tokens): array
	{
		$aliases = [];
		foreach ((new NamespaceUsesAnalyzer)->getDeclarationsFromTokens($tokens, true) as $use) { // true = also grouped imports
			if ($use->isClass()) {
				$aliases[strtolower($use->getShortName())] = true;
			}
		}

		return $aliases;
	}


	/**
	 * Dropping the slash would route the name through an import instead of the global namespace.
	 * @param  array<string, true>  $aliases
	 */
	private static function isShadowedByImport(Tokens $tokens, int $index, array $aliases): bool
	{
		$nextIndex = $tokens->getNextMeaningfulToken($index);

		return $nextIndex !== null
			&& $tokens[$nextIndex]->isGivenKind(T_STRING)
			&& isset($aliases[strtolower($tokens[$nextIndex]->getContent())]);
	}


	private static function isToRemove(Tokens $tokens, int $index): bool
	{
		if (!$tokens[$index]->isGivenKind(T_NS_SEPARATOR)) {
			return false;
		}

		$prevIndex = $tokens->getPrevMeaningfulToken($index);
		assert(is_int($prevIndex));

		if ($tokens[$prevIndex]->isGivenKind(T_STRING)) {
			return false;
		}
		if ($tokens[$prevIndex]->isGivenKind([T_NEW, CT::T_NULLABLE_TYPE, CT::T_TYPE_COLON])) {
			return true;
		}

		$nextIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[T_COMMENT], [T_DOC_COMMENT], [T_NS_SEPARATOR], [T_STRING], [T_WHITESPACE]]);
		assert(is_int($nextIndex));

		if ($tokens[$nextIndex]->isGivenKind(T_DOUBLE_COLON)) {
			return true;
		}

		return $tokens[$prevIndex]->equalsAny(['(', ',', [CT::T_TYPE_ALTERNATION]]) && $tokens[$nextIndex]->isGivenKind([T_VARIABLE, CT::T_TYPE_ALTERNATION]);
	}


	private static function skipNamespacedCode(Tokens $tokens, int $index): int
	{
		if (!$tokens[$index]->isGivenKind(T_NAMESPACE)) {
			return $index;
		}

		$nextIndex = $tokens->getNextMeaningfulToken($index);
		assert(is_int($nextIndex));

		if ($tokens[$nextIndex]->equals('{')) {
			return $nextIndex;
		}

		$nextIndex = $tokens->getNextTokenOfKind($index, ['{', ';']);
		assert(is_int($nextIndex));

		if ($tokens[$nextIndex]->equals(';')) {
			return $tokens->count() - 1;
		}

		return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $nextIndex); // the newer BLOCK_TYPE_BRACE alias is missing in php-cs-fixer 3.87
	}
}
