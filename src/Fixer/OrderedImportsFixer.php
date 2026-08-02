<?php declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// PhpCsFixer\Fixer\Import;

namespace NetteCodingStandard\Fixer\Import;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;


/**
 * Unlike the upstream fixer, a comma-separated `use` statement is treated as one unit: the
 * upstream one keeps the original statement prefixes in place and pours the sorted names back
 * into them, so with mixed types the names leak across the boundary and `use function` ends up
 * holding constants or classes. Here the whole block is regenerated instead, so a name can never
 * change its import type. A type imported via a comma-separated statement stays merged into one.
 */
final class OrderedImportsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
	/** import types in the order they are emitted */
	private const Types = ['', 'function', 'const'];


	public function getDefinition(): FixerDefinitionInterface
	{
		return new FixerDefinition(
			'Ordering `use` imports by type (class, function, const) and then alphabetically.',
			[new CodeSample("<?php\nuse const PHP_EOL;\nuse function count, strlen;\nuse Foo\\Bar;\n")],
		);
	}


	/**
	 * Must run before BlankLineBetweenImportGroupsFixer.
	 * Must run after FullyQualifiedStrictTypesFixer, GlobalNamespaceImportFixer, NoLeadingImportSlashFixer.
	 */
	public function getPriority(): int
	{
		return -30; // same as the upstream fixer it replaces
	}


	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isTokenKindFound(T_USE);
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
		foreach (array_reverse(self::findBlocks($tokens)) as $block) {
			self::fixBlock($tokens, $block);
		}
	}


	/**
	 * Import statements split into blocks of directly consecutive ones. Code between two
	 * imports starts a new block, because reordering across it would move the imports past
	 * that code. A comment does not split the block; it makes the block untouchable instead.
	 * @return list<list<int>>  indices of the `use` tokens
	 */
	private static function findBlocks(Tokens $tokens): array
	{
		$blocks = [];
		foreach ((new TokensAnalyzer($tokens))->getImportUseIndexes(true) as $indices) {
			$block = [];
			$prevEnd = null;
			foreach ($indices as $index) {
				if ($prevEnd !== null && $tokens->getNextMeaningfulToken($prevEnd) !== $index) {
					$blocks[] = $block;
					$block = [];
				}
				$block[] = $index;
				$prevEnd = $tokens->getNextTokenOfKind($index, [';']);
				if ($prevEnd === null) { // unterminated statement, do not touch the file
					return [];
				}
			}
			$blocks[] = $block;
		}

		return array_values(array_filter($blocks));
	}


	/**
	 * @param  list<int>  $block
	 */
	private static function fixBlock(Tokens $tokens, array $block): void
	{
		$start = $block[0];
		$end = $tokens->getNextTokenOfKind($block[count($block) - 1], [';']);
		if ($end === null) {
			return;
		}

		// the block is rewritten as a whole, which would drop a comment or move it to
		// a statement it does not belong to, so a commented block is left alone
		for ($i = $start; $i < $end; $i++) {
			if ($tokens[$i]->isComment()) {
				return;
			}
		}
		if (self::hasTrailingComment($tokens, $end)) {
			return;
		}

		$imports = self::parseBlock($tokens, $block);
		if ($imports === null) {
			return;
		}

		[$names, $slots] = $imports;
		$code = self::buildCode($names, $slots, self::getSeparator($tokens, $start));
		if ($code === $tokens->generatePartialCode($start, $end)) {
			return;
		}

		$newTokens = Tokens::fromCode('<?php ' . $code);
		$newTokens->clearAt(0); // the `<?php ` opening tag
		$newTokens->clearEmptyTokens();
		$tokens->overrideRange($start, $end, $newTokens);
	}


	/**
	 * A comment on the same line as the block's last semicolon belongs to that statement,
	 * even though it sits outside the rewritten range. One on its own line belongs to
	 * whatever follows the imports.
	 */
	private static function hasTrailingComment(Tokens $tokens, int $end): bool
	{
		$next = $tokens->getNextNonWhitespace($end);
		if ($next === null || !$tokens[$next]->isComment()) {
			return false;
		}

		for ($i = $end + 1; $i < $next; $i++) {
			if (str_contains($tokens[$i]->getContent(), "\n")) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Imported names grouped by type, plus how many names each statement of that type held.
	 * The statement sizes are kept so that the block keeps its shape and only the ordering
	 * changes. Returns null when the block holds anything this fixer must not rewrite.
	 * @param  list<int>  $block
	 * @return ?array{array<string, list<string>>, array<string, list<int>>}
	 */
	private static function parseBlock(Tokens $tokens, array $block): ?array
	{
		$names = array_fill_keys(self::Types, []);
		$slots = array_fill_keys(self::Types, []);

		foreach ($block as $useIndex) {
			$end = $tokens->getNextTokenOfKind($useIndex, [';']);
			$index = $tokens->getNextMeaningfulToken($useIndex);
			$type = '';
			if ($tokens[$index]->isGivenKind(CT::T_FUNCTION_IMPORT)) {
				$type = 'function';
				$index = $tokens->getNextMeaningfulToken($index);
			} elseif ($tokens[$index]->isGivenKind(CT::T_CONST_IMPORT)) {
				$type = 'const';
				$index = $tokens->getNextMeaningfulToken($index);
			}

			$chunk = '';
			$count = $depth = 0;
			for (; $index < $end; $index++) {
				$token = $tokens[$index];
				if ($token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_OPEN)) {
					$depth++; // a `use Foo\{A, B}` import is one item, its commas do not separate
					$chunk .= $token->getContent();
				} elseif ($token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
					$depth--;
					$chunk .= $token->getContent();
				} elseif ($token->equals(',') && $depth === 0) {
					$names[$type][] = trim($chunk);
					$chunk = '';
					$count++;
				} elseif ($token->isWhitespace()) {
					$chunk .= ' ';
				} else {
					$chunk .= $token->getContent();
				}
			}

			$names[$type][] = trim($chunk);
			$slots[$type][] = $count + 1;
		}

		foreach ($names as $list) {
			foreach ($list as $name) {
				if ($name === '') {
					return null; // a trailing comma or something else unexpected
				}
			}
		}

		return [$names, $slots];
	}


	/**
	 * @param  array<string, list<string>>  $names
	 * @param  array<string, list<int>>  $slots
	 */
	private static function buildCode(array $names, array $slots, string $separator): string
	{
		$statements = [];
		foreach (self::Types as $type) {
			if (!$names[$type]) {
				continue;
			}

			$pool = $names[$type];
			usort($pool, [self::class, 'compareNames']); // no first-class callable syntax, PHP 8.0 is supported
			$prefix = 'use ' . ($type === '' ? '' : $type . ' ');
			foreach ($slots[$type] as $size) {
				$statements[] = $prefix . implode(', ', array_splice($pool, 0, $size)) . ';';
			}
		}

		return implode($separator, $statements);
	}


	/**
	 * Backslashes sort before any name character, so that a namespace and its sub-namespace
	 * keep their natural order. Matches the upstream alphabetical algorithm.
	 */
	private static function compareNames(string $first, string $second): int
	{
		$replace = ['\\' => ' ', '{' => ''];
		return strcasecmp(strtr($first, $replace), strtr($second, $replace));
	}


	/** line ending plus indentation, i.e. what separates two statements of the block */
	private static function getSeparator(Tokens $tokens, int $start): string
	{
		$eol = "\n";
		$indent = '';
		if ($start > 0 && $tokens[$start - 1]->isWhitespace()) {
			$content = $tokens[$start - 1]->getContent();
			if (preg_match('~(\r\n|\n|\r)([ \t]*)$~', $content, $m)) {
				[, $eol, $indent] = $m;
			}
		}

		return $eol . $indent;
	}
}
