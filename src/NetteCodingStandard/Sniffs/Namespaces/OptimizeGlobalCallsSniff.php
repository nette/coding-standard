<?php

declare(strict_types=1);

/**
 * Optimizes global function and constant calls by generating grouped `use` statements.
 *
 * PHP's runtime resolution of function and constant names can be suboptimal. When a function
 * like `strlen()` is called from within a namespace, PHP first attempts to resolve it in the
 * current namespace (`My\App\strlen`) before falling back to the global scope. This fallback
 * has a performance cost and, more importantly, prevents the PHP compiler from applying
 * special optimizations to certain core functions (e.g., `strlen`, `count`).
 *
 * The solution is to explicitly import a global symbol into the current namespace via a
 * `use function` or `use const` statement. This sniff automates that process.
 *
 * This sniff operates in two modes:
 *
 * 1. ALL FUNCTIONS MODE (`optimizedFunctionsOnly=false`, default):
 *    - Finds ALL global function and constant calls.
 *    - Generates grouped `use` statements for them, promoting code clarity and
 *      consistency by reducing backslashes (`\`) in the code body.
 *
 * 2. OPTIMIZED FUNCTIONS ONLY MODE (`optimizedFunctionsOnly=true`):
 *    - Focuses solely on functions that the PHP compiler can optimize (from `zend_compile.c`).
 *    - Ensures these specific functions are imported to enable special opcodes, which is
 *      ideal for performance-critical projects.
 *
 * Configuration example in your `ruleset.xml`:
 * ```xml
 * <rule ref="NetteCodingStandard.Namespaces.OptimizeGlobalCalls">
 *     <properties>
 *         <property name="optimizedFunctionsOnly" value="false"/>
 *         <property name="ignoredFunctions" type="array">
 *             <element value="dump"/>
 *             <element value="dd"/>
 *         </property>
 *         <property name="ignoredConstants" type="array">
 *             <element value="SOME_CONSTANT"/>
 *         </property>
 *     </properties>
 * </rule>
 * ```
 */

namespace NetteCodingStandard\Sniffs\Namespaces;

use Exception;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use function count, defined, in_array;


class OptimizeGlobalCallsSniff implements Sniff
{
	public $optimizedFunctionsOnly = true;
	public $ignoredFunctions = [];
	public $ignoredConstants = [];
	private static $processedFiles = [];

	private $compilerOptimizedFunctions = [
		'strlen', 'is_null', 'is_bool', 'is_long', 'is_int', 'is_integer',
		'is_float', 'is_double', 'is_string', 'is_array', 'is_object',
		'is_resource', 'is_scalar', 'boolval', 'intval', 'floatval',
		'doubleval', 'strval', 'defined', 'chr', 'ord', 'call_user_func_array',
		'call_user_func', 'in_array', 'count', 'sizeof', 'get_class',
		'get_called_class', 'gettype', 'func_num_args', 'func_get_args',
		'array_slice', 'array_key_exists', 'sprintf',
	];

	private $builtInIgnoredConstants = [
		'TRUE', 'FALSE', 'NULL',
	];


	public function register(): array
	{
		return [T_OPEN_TAG];
	}


	public function process(File $phpcsFile, $stackPtr)
	{
		if ($stackPtr > 0) {
			return;
		}

		$filename = $phpcsFile->getFilename();

		if (isset(self::$processedFiles[$filename])) {
			return;
		}

		try {
			if (!$this->hasNamespace($phpcsFile)) {
				self::$processedFiles[$filename] = true;
				return;
			}

			$existingUseStatements = $this->findExistingUseStatements($phpcsFile);

			$usedFunctions = $this->findUsedGlobalFunctions($phpcsFile, $existingUseStatements);
			$usedConstants = $this->findUsedGlobalConstants($phpcsFile, $existingUseStatements);

			$finalFunctions = $usedFunctions;
			if ($this->optimizedFunctionsOnly) {
				$nonOptimizedToKeep = [];
				foreach ($existingUseStatements['all_functions'] as $name) {
					if (!in_array(strtolower($name), $this->compilerOptimizedFunctions, true)) {
						if ($this->isFunctionUsedInCode($phpcsFile, $name)) {
							$nonOptimizedToKeep[] = $name;
						}
					}
				}
				$finalFunctions = array_values(array_unique(array_merge($finalFunctions, $nonOptimizedToKeep)));
			}

			$finalConstants = $usedConstants;

			$isCorrect = $this->isStateCorrect($phpcsFile, $finalFunctions, $finalConstants, $existingUseStatements);
			$hasBackslashesToRemove = $this->hasBackslashesToRemove($phpcsFile, $finalFunctions, $finalConstants);

			if ($isCorrect && !$hasBackslashesToRemove) {
				self::$processedFiles[$filename] = true;
				return;
			}

			$fixMessage = 'Global functions and constants should be imported via `use` statements for performance and clarity.';
			$fix = $phpcsFile->addFixableError($fixMessage, $stackPtr, 'ImportGlobalSymbols');

			if ($fix === true) {
				$success = $this->applyFixWithErrorHandling($phpcsFile, $finalFunctions, $finalConstants, $existingUseStatements);
				if ($success) {
					self::$processedFiles[$filename] = true;
				}
			}
		} catch (\Throwable $e) {
			return;
		}
	}


	private function applyFixWithErrorHandling(
		File $phpcsFile,
		array $finalFunctions,
		array $finalConstants,
		array $existingUseStatements,
	): bool
	{
		try {
			$phpcsFile->fixer->beginChangeset();

			$this->processUseStatements($phpcsFile, 'function', $finalFunctions, $existingUseStatements['functions']);
			$this->processUseStatements($phpcsFile, 'const', $finalConstants, $existingUseStatements['constants']);
			$this->removeBackslashesFromCode($phpcsFile, $finalFunctions, $finalConstants);

			$phpcsFile->fixer->endChangeset();
			return true;
		} catch (\Throwable $e) {
			$phpcsFile->fixer->rollbackChangeset();
			return false;
		}
	}


	private function processUseStatements(File $phpcsFile, string $type, array $finalNames, array $existingStmts)
	{
		if (empty($existingStmts) && empty($finalNames)) {
			return;
		}

		if (empty($finalNames)) {
			foreach ($existingStmts as $stmt) {
				$this->deleteUseStatement($phpcsFile, $stmt);
			}
			return;
		}

		if (empty($existingStmts)) {
			$this->addNewUseBlock($phpcsFile, $type, $finalNames);
			return;
		}

		$mainStmt = array_shift($existingStmts);
		foreach ($existingStmts as $stmt) {
			$this->deleteUseStatement($phpcsFile, $stmt);
		}

		$this->replaceUseContent($phpcsFile, $mainStmt, $finalNames);
	}


	private function deleteUseStatement(File $phpcsFile, array $statement)
	{
		$lineStart = $statement['start'];
		while ($lineStart > 0 && $phpcsFile->getTokens()[$lineStart - 1]['line'] === $statement['line']) {
			$lineStart--;
		}

		$lineEnd = $statement['end'];
		if (
			isset($phpcsFile->getTokens()[$lineEnd + 1])
			&& preg_match('/^(\r\n|\n|\r)/', $phpcsFile->getTokens()[$lineEnd + 1]['content'])
		) {
			$lineEnd++;
		}

		for ($i = $lineStart; $i <= $lineEnd; $i++) {
			$phpcsFile->fixer->replaceToken($i, '');
		}
	}


	private function replaceUseContent(File $phpcsFile, array $statement, array $finalNames)
	{
		sort($finalNames);
		$startContentPtr = $phpcsFile->findNext(T_STRING, $statement['start']);
		$startContentPtr = $phpcsFile->findNext(T_WHITESPACE, $startContentPtr + 1, null, true);
		$endContentPtr = $phpcsFile->findPrevious(T_SEMICOLON, $statement['end']);

		for ($i = $startContentPtr; $i < $endContentPtr; $i++) {
			$phpcsFile->fixer->replaceToken($i, '');
		}

		$phpcsFile->fixer->addContentBefore($endContentPtr, implode(', ', $finalNames));
	}


	private function addNewUseBlock(File $phpcsFile, string $type, array $names)
	{
		$insertPointInfo = $this->findInsertionPointInfo($phpcsFile);
		if ($insertPointInfo === null) {
			return;
		}

		$insertPosition = $insertPointInfo['position'];
		$afterType = $insertPointInfo['after'];

		sort($names);
		$eol = $phpcsFile->eolChar;
		$content = 'use ' . $type . ' ' . implode(', ', $names) . ';';

		$prefix = ($afterType === 'namespace') ? $eol . $eol : $eol;

		$phpcsFile->fixer->addContent($insertPosition, $prefix . $content);
	}


	private function isStateCorrect(
		File $phpcsFile,
		array $finalFunctions,
		array $finalConstants,
		array $existingUseStatements,
	): bool
	{
		sort($finalFunctions);
		sort($finalConstants);

		$currentFunctions = $existingUseStatements['all_functions'];
		$currentConstants = $existingUseStatements['all_constants'];
		sort($currentFunctions);
		sort($currentConstants);

		if ($finalFunctions !== $currentFunctions) {
			return false;
		}

		if (!$this->constantArraysMatch($finalConstants, $currentConstants)) {
			return false;
		}

		return count($existingUseStatements['functions']) <= 1 && count($existingUseStatements['constants']) <= 1;
	}


	private function isFunctionUsedInCode(File $phpcsFile, string $functionName): bool
	{
		$tokens = $phpcsFile->getTokens();
		for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
			if ($tokens[$i]['code'] === T_STRING && strtolower($tokens[$i]['content']) === strtolower($functionName)) {
				$nextToken = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);
				if ($nextToken !== false && $tokens[$nextToken]['code'] === T_OPEN_PARENTHESIS) {
					$prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
					if (
						$prevToken === false
						|| !in_array($tokens[$prevToken]['code'], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR, T_FUNCTION], true)
					) {
						return true;
					}
				}
			}
		}
		return false;
	}


	private function isFunctionDeclaration(File $phpcsFile, int $stackPtr): bool
	{
		$prevSemicolon = $phpcsFile->findPrevious(T_SEMICOLON, $stackPtr - 1);
		$prevOpenBrace = $phpcsFile->findPrevious(T_OPEN_CURLY_BRACKET, $stackPtr - 1);
		$boundary = max($prevSemicolon, $prevOpenBrace);
		if ($boundary === false) {
			$boundary = null;
		}
		$functionKeyword = $phpcsFile->findPrevious(T_FUNCTION, $stackPtr - 1, $boundary);
		return $functionKeyword !== false;
	}


	private function isWithinUseStatement(int $stackPtr, array $existingUseStatements): bool
	{
		$allStatements = array_merge($existingUseStatements['functions'], $existingUseStatements['constants']);
		foreach ($allStatements as $statement) {
			if ($stackPtr >= $statement['start'] && $stackPtr <= $statement['end']) {
				return true;
			}
		}
		return false;
	}


	private function findUsedGlobalFunctions(File $phpcsFile, array $existingUseStatements): array
	{
		$tokens = $phpcsFile->getTokens();
		$usedFunctions = [];
		$ignoredFunctions = array_map('strtolower', $this->ignoredFunctions);

		for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
			if (
				$this->isWithinUseStatement($i, $existingUseStatements)
				|| $this->isFunctionDeclaration($phpcsFile, $i)
			) {
				continue;
			}

			if ($tokens[$i]['code'] !== T_STRING) {
				continue;
			}

			$functionName = $tokens[$i]['content'];
			if (in_array(strtolower($functionName), $ignoredFunctions, true)) {
				continue;
			}

			$nextToken = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);
			if ($nextToken === false || $tokens[$nextToken]['code'] !== T_OPEN_PARENTHESIS) {
				continue;
			}

			$prevTokenPtr = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
			if (
				$prevTokenPtr !== false
				&& in_array($tokens[$prevTokenPtr]['code'], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR], true)
			) {
				continue;
			}

			if ($prevTokenPtr !== false && $tokens[$prevTokenPtr]['code'] === T_NS_SEPARATOR) {
				$beforeBackslash = $phpcsFile->findPrevious(T_WHITESPACE, $prevTokenPtr - 1, null, true);
				if ($beforeBackslash !== false && $tokens[$beforeBackslash]['code'] === T_STRING) {
					continue;
				}
			}

			if ($this->optimizedFunctionsOnly) {
				if (!in_array(strtolower($functionName), $this->compilerOptimizedFunctions, true)) {
					continue;
				}
			} else {
				if (!function_exists($functionName)) {
					continue;
				}
			}
			$usedFunctions[] = $functionName;
		}
		return array_values(array_unique($usedFunctions));
	}


	private function findUsedGlobalConstants(File $phpcsFile, array $existingUseStatements): array
	{
		$tokens = $phpcsFile->getTokens();
		$usedConstants = [];

		for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
			if ($this->isWithinUseStatement($i, $existingUseStatements)) {
				continue;
			}

			if ($tokens[$i]['code'] !== T_STRING) {
				continue;
			}

			$constantName = $tokens[$i]['content'];
			if ($this->isIgnoredConstant($constantName)) {
				continue;
			}

			$prevTokenPtr = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
			$nextTokenPtr = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);

			if ($nextTokenPtr !== false && $tokens[$nextTokenPtr]['code'] === T_OPEN_PARENTHESIS) {
				continue;
			}

			if (
				$prevTokenPtr !== false
				&& in_array($tokens[$prevTokenPtr]['code'], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR, T_COLON], true)
			) {
				continue;
			}

			if ($prevTokenPtr !== false && $tokens[$prevTokenPtr]['code'] === T_NS_SEPARATOR) {
				$beforeBackslash = $phpcsFile->findPrevious(T_WHITESPACE, $prevTokenPtr - 1, null, true);
				if ($beforeBackslash !== false && $tokens[$beforeBackslash]['code'] === T_STRING) {
					continue;
				}
			}

			if (
				$nextTokenPtr !== false
				&& (
					$tokens[$nextTokenPtr]['code'] === T_DOUBLE_COLON
					|| $tokens[$nextTokenPtr]['code'] === T_NS_SEPARATOR
				)
			) {
				continue;
			}

			if (!defined($constantName)) {
				continue;
			}
			$usedConstants[] = $constantName;
		}
		return array_values(array_unique($usedConstants));
	}


	private function findInsertionPointInfo(File $phpcsFile): ?array
	{
		$tokens = $phpcsFile->getTokens();
		$lastUsePos = null;

		for ($i = $phpcsFile->numTokens - 1; $i >= 0; $i--) {
			if ($tokens[$i]['code'] === T_USE && $this->isTopLevelUseStatement($phpcsFile, $i)) {
				$lastUsePos = $i;
				break;
			}
		}

		if ($lastUsePos !== null) {
			$semicolonPos = $phpcsFile->findNext(T_SEMICOLON, $lastUsePos);
			if ($semicolonPos !== false) {
				return ['position' => $semicolonPos, 'after' => 'use'];
			}
		}

		$namespacePos = $phpcsFile->findNext(T_NAMESPACE, 0);
		if ($namespacePos !== false) {
			$semicolonPos = $phpcsFile->findNext(T_SEMICOLON, $namespacePos);
			if ($semicolonPos !== false) {
				return ['position' => $semicolonPos, 'after' => 'namespace'];
			}
		}

		return null;
	}


	private function findExistingUseStatements(File $phpcsFile): array
	{
		$tokens = $phpcsFile->getTokens();
		$useStatements = ['functions' => [], 'constants' => [], 'all_functions' => [], 'all_constants' => []];

		for ($i = 0; $i < count($tokens); $i++) {
			if ($tokens[$i]['code'] !== T_USE) {
				continue;
			}

			$useStatement = $this->parseUseStatement($phpcsFile, $i);
			if ($useStatement === null || !$this->isTopLevelUseStatement($phpcsFile, $i)) {
				continue;
			}

			if ($useStatement['type'] === 'function' && $useStatement['is_global']) {
				$useStatements['functions'][] = $useStatement;
				$useStatements['all_functions'] = array_merge($useStatements['all_functions'], $useStatement['names']);
			} elseif ($useStatement['type'] === 'const' && $useStatement['is_global']) {
				$useStatements['constants'][] = $useStatement;
				$useStatements['all_constants'] = array_merge($useStatements['all_constants'], $useStatement['names']);
			}
		}
		$useStatements['all_functions'] = array_values(array_unique($useStatements['all_functions']));
		$useStatements['all_constants'] = array_values(array_unique($useStatements['all_constants']));
		return $useStatements;
	}


	private function parseUseStatement(File $phpcsFile, int $usePtr): ?array
	{
		$tokens = $phpcsFile->getTokens();
		$nextToken = $phpcsFile->findNext(T_WHITESPACE, $usePtr + 1, null, true);
		if ($nextToken === false) {
			return null;
		}

		$type = null;
		$startNamePtr = $nextToken;

		if ($tokens[$nextToken]['code'] === T_STRING) {
			$content = strtolower($tokens[$nextToken]['content']);
			if ($content === 'function' || $content === 'const') {
				$type = $content;
				$startNamePtr = $phpcsFile->findNext(T_WHITESPACE, $nextToken + 1, null, true);
			}
		}

		if ($startNamePtr === false) {
			return null;
		}

		$endPtr = $phpcsFile->findNext(T_SEMICOLON, $startNamePtr);
		if ($endPtr === false) {
			return null;
		}

		$names = [];
		$currentName = '';
		$isGlobal = true;

		for ($i = $startNamePtr; $i < $endPtr; $i++) {
			if ($tokens[$i]['code'] === T_WHITESPACE) {
				continue;
			}
			if ($tokens[$i]['content'] === ',') {
				if ($currentName !== '') {
					$names[] = trim($currentName);
					$currentName = '';
				}
				continue;
			}
			if ($tokens[$i]['code'] === T_NS_SEPARATOR && $currentName !== '') {
				$isGlobal = false;
			}
			$currentName .= $tokens[$i]['content'];
		}

		if ($currentName !== '') {
			$names[] = trim($currentName);
		}

		return [
			'start' => $usePtr, 'end' => $endPtr, 'type' => $type,
			'names' => $names, 'is_global' => $isGlobal, 'line' => $tokens[$usePtr]['line'],
		];
	}


	private function removeBackslashesFromCode(File $phpcsFile, array $usedFunctions, array $usedConstants): void
	{
		if (empty($usedFunctions) && empty($usedConstants)) {
			return;
		}

		$tokens = $phpcsFile->getTokens();
		$lookups = $this->createLookupArrays($usedFunctions, $usedConstants);

		for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
			if ($tokens[$i]['code'] !== T_NS_SEPARATOR) {
				continue;
			}

			$prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
			if ($prevToken !== false && $tokens[$prevToken]['code'] === T_STRING) {
				continue;
			}

			$nextToken = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);
			if ($nextToken === false || $tokens[$nextToken]['code'] !== T_STRING) {
				continue;
			}

			$name = $tokens[$nextToken]['content'];
			$nameLower = strtolower($name);
			$afterName = $phpcsFile->findNext(T_WHITESPACE, $nextToken + 1, null, true);

			if ($afterName !== false && $tokens[$afterName]['code'] === T_OPEN_PARENTHESIS) {
				if (isset($lookups['functions'][$nameLower])) {
					$phpcsFile->fixer->replaceToken($i, '');
				}
			} elseif ($this->isConstantContext($afterName, $tokens)) {
				if (isset($lookups['constants'][$nameLower])) {
					$phpcsFile->fixer->replaceToken($i, '');
				}
			}
		}
	}


	private function hasNamespace(File $phpcsFile): bool
	{
		return $phpcsFile->findNext(T_NAMESPACE, 0) !== false;
	}


	private function isIgnoredConstant(string $constantName): bool
	{
		$ignored = array_merge($this->builtInIgnoredConstants, $this->ignoredConstants);
		foreach ($ignored as $pattern) {
			$regex = str_replace('\*', '.*', preg_quote($pattern, '/'));
			if (preg_match('/^' . $regex . '$/i', $constantName)) {
				return true;
			}
		}

		return false;
	}


	private function createLookupArrays(array $usedFunctions, array $usedConstants): array
	{
		$functionLookup = [];
		foreach ($usedFunctions as $func) {
			$functionLookup[strtolower($func)] = $func;
		}
		$constantLookup = [];
		foreach ($usedConstants as $const) {
			$constantLookup[strtolower($const)] = $const;
		}
		return ['functions' => $functionLookup, 'constants' => $constantLookup];
	}


	private function hasBackslashesToRemove(File $phpcsFile, array $usedFunctions, array $usedConstants): bool
	{
		if (empty($usedFunctions) && empty($usedConstants)) {
			return false;
		}

		$tokens = $phpcsFile->getTokens();
		$lookups = $this->createLookupArrays($usedFunctions, $usedConstants);

		for ($i = 0; $i < count($tokens); $i++) {
			if ($tokens[$i]['code'] !== T_NS_SEPARATOR) {
				continue;
			}

			$prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
			if ($prevToken !== false && $tokens[$prevToken]['code'] === T_STRING) {
				continue;
			}

			$nextToken = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);
			if ($nextToken === false || $tokens[$nextToken]['code'] !== T_STRING) {
				continue;
			}

			$name = $tokens[$nextToken]['content'];
			$nameLower = strtolower($name);
			$afterName = $phpcsFile->findNext(T_WHITESPACE, $nextToken + 1, null, true);

			if ($afterName !== false && $tokens[$afterName]['code'] === T_OPEN_PARENTHESIS) {
				if (isset($lookups['functions'][$nameLower])) {
					return true;
				}
			} elseif ($this->isConstantContext($afterName, $tokens)) {
				if (isset($lookups['constants'][$nameLower])) {
					return true;
				}
			}
		}
		return false;
	}


	private function constantArraysMatch(array $existing, array $required): bool
	{
		if (count($existing) !== count($required)) {
			return false;
		}
		$existingNormalized = array_map('strtolower', $existing);
		$requiredNormalized = array_map('strtolower', $required);
		sort($existingNormalized);
		sort($requiredNormalized);
		return $existingNormalized === $requiredNormalized;
	}


	private function isConstantContext(?int $afterNamePos, array $tokens): bool
	{
		if ($afterNamePos === null || !isset($tokens[$afterNamePos])) {
			return true;
		}
		$tokenCode = $tokens[$afterNamePos]['code'];
		return !in_array($tokenCode, [T_OPEN_PARENTHESIS, T_DOUBLE_COLON, T_NS_SEPARATOR], true);
	}


	private function isTopLevelUseStatement(File $phpcsFile, int $usePos): bool
	{
		return empty($phpcsFile->getTokens()[$usePos]['conditions']);
	}
}
