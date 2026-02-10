<?php

declare(strict_types=1);

namespace NetteCodingStandard\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;


class DeclareStrictTypesSniff implements Sniff
{
	public function register(): array
	{
		return [T_OPEN_TAG];
	}


	public function process(File $phpcsFile, $stackPtr): void
	{
		$tokens = $phpcsFile->getTokens();

		// only process the first open tag
		if ($stackPtr !== 0) {
			return;
		}

		// find declare(strict_types=1)
		$declarePtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if ($declarePtr === false || $tokens[$declarePtr]['code'] !== T_DECLARE) {
			return;
		}

		// check if declare is on the same line as <?php
		$openTagLine = $tokens[$stackPtr]['line'];
		$declareLine = $tokens[$declarePtr]['line'];

		if ($openTagLine === $declareLine) {
			return; // already on the same line
		}

		$fix = $phpcsFile->addFixableError(
			'declare(strict_types=1) should be on the same line as the opening PHP tag',
			$declarePtr,
			'WrongLine',
		);

		if ($fix) {
			$phpcsFile->fixer->beginChangeset();
			// remove whitespace between <?php and declare
			for ($i = $stackPtr + 1; $i < $declarePtr; $i++) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
			// replace <?php\n with <?php + space
			$phpcsFile->fixer->replaceToken($stackPtr, '<?php ');
			$phpcsFile->fixer->endChangeset();
		}
	}
}
