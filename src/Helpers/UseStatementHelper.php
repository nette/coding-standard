<?php declare(strict_types = 1);

/**
 * Checks the separation between methods in a class or interface.
 *
 * @author    Ondrej Mirtes
 * @copyright Slevomat.cz
 * @license   https://github.com/slevomat/coding-standard/blob/master/LICENSE.md
 */

namespace Nette\CodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use function array_key_exists;
use function array_merge;
use function in_array;
use function sprintf;
use const T_ANON_CLASS;
use const T_AS;
use const T_COMMA;
use const T_OPEN_PARENTHESIS;
use const T_SEMICOLON;
use const T_STRING;
use const T_USE;

class UseStatementHelper
{

    /** @var \SlevomatCodingStandard\Helpers\UseStatement[][] Cached data for method getUseStatements() */
    private static $allUseStatements = [];

    public static function isAnonymousFunctionUse(File $phpcsFile, int $usePointer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $nextPointer = TokenHelper::findNextEffective($phpcsFile, $usePointer + 1);
        $nextToken = $tokens[$nextPointer];

        return $nextToken['code'] === T_OPEN_PARENTHESIS;
    }

    public static function isTraitUse(File $phpcsFile, int $usePointer): bool
    {
        $typePointer = TokenHelper::findPrevious($phpcsFile, array_merge(TokenHelper::$typeKeywordTokenCodes, [T_ANON_CLASS]), $usePointer);
        if ($typePointer !== null) {
            $tokens = $phpcsFile->getTokens();
            $typeToken = $tokens[$typePointer];
            $openerPointer = $typeToken['scope_opener'];
            $closerPointer = $typeToken['scope_closer'];

            return $usePointer > $openerPointer && $usePointer < $closerPointer
                && !self::isAnonymousFunctionUse($phpcsFile, $usePointer);
        }

        return false;
    }

    public static function getAlias(File $phpcsFile, int $usePointer): ?string
    {
        $endPointer = TokenHelper::findNext($phpcsFile, [T_SEMICOLON, T_COMMA], $usePointer + 1);
        $asPointer = TokenHelper::findNext($phpcsFile, T_AS, $usePointer + 1, $endPointer);

        if ($asPointer === null) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();
        return $tokens[TokenHelper::findNext($phpcsFile, T_STRING, $asPointer + 1)]['content'];
    }

    public static function getNameAsReferencedInClassFromUse(File $phpcsFile, int $usePointer): string
    {
        $alias = self::getAlias($phpcsFile, $usePointer);
        if ($alias !== null) {
            return $alias;
        }

        $name = self::getFullyQualifiedTypeNameFromUse($phpcsFile, $usePointer);
        return NamespaceHelper::getUnqualifiedNameFromFullyQualifiedName($name);
    }

    public static function getFullyQualifiedTypeNameFromUse(File $phpcsFile, int $usePointer): string
    {
        $tokens = $phpcsFile->getTokens();

        $nameEndPointer = TokenHelper::findNext($phpcsFile, [T_SEMICOLON, T_AS, T_COMMA], $usePointer + 1) - 1;
        if (in_array($tokens[$nameEndPointer]['code'], TokenHelper::$ineffectiveTokenCodes, true)) {
            $nameEndPointer = TokenHelper::findPreviousEffective($phpcsFile, $nameEndPointer);
        }
        $nameStartPointer = TokenHelper::findPreviousExcluding($phpcsFile, TokenHelper::$nameTokenCodes, $nameEndPointer - 1) + 1;

        $name = TokenHelper::getContent($phpcsFile, $nameStartPointer, $nameEndPointer);

        return NamespaceHelper::normalizeToCanonicalName($name);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openTagPointer
     * @return \SlevomatCodingStandard\Helpers\UseStatement[] canonicalName(string) => useStatement(\SlevomatCodingStandard\Helpers\UseStatement)
     */
    public static function getUseStatements(File $phpcsFile, int $openTagPointer): array
    {
        $cacheKey = sprintf('%s-%s', $phpcsFile->getFilename(), $openTagPointer);

        $fixerLoops = $phpcsFile->fixer !== null ? $phpcsFile->fixer->loops : null;
        if ($fixerLoops !== null) {
            $cacheKey .= '-loop' . $fixerLoops;
            if ($fixerLoops > 0) {
                unset(self::$allUseStatements[$cacheKey . '-loop' . ($fixerLoops - 1)]);
            }
        }

        if (!array_key_exists($cacheKey, self::$allUseStatements)) {
            $useStatements = [];
            $tokens = $phpcsFile->getTokens();
            foreach (self::getUseStatementPointers($phpcsFile, $openTagPointer) as $usePointer) {
                $nextTokenFromUsePointer = TokenHelper::findNextEffective($phpcsFile, $usePointer + 1);
                $type = UseStatement::TYPE_DEFAULT;
                if ($tokens[$nextTokenFromUsePointer]['code'] === T_STRING) {
                    if ($tokens[$nextTokenFromUsePointer]['content'] === 'const') {
                        $type = UseStatement::TYPE_CONSTANT;
                    } elseif ($tokens[$nextTokenFromUsePointer]['content'] === 'function') {
                        $type = UseStatement::TYPE_FUNCTION;
                    }
                }
                $name = self::getNameAsReferencedInClassFromUse($phpcsFile, $usePointer);
                $useStatement = new UseStatement(
                    $name,
                    self::getFullyQualifiedTypeNameFromUse($phpcsFile, $usePointer),
                    $usePointer,
                    $type,
                    self::getAlias($phpcsFile, $usePointer)
                );
                $useStatements[UseStatement::getUniqueId($type, $name)] = $useStatement;
            }

            self::$allUseStatements[$cacheKey] = $useStatements;
        }

        return self::$allUseStatements[$cacheKey];
    }

    /**
     * Searches for all use statements in a file, skips bodies of classes and traits.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openTagPointer
     * @return int[]
     */
    private static function getUseStatementPointers(File $phpcsFile, int $openTagPointer): array
    {
        $tokens = $phpcsFile->getTokens();
        $pointer = $openTagPointer + 1;
        $pointers = [];
        while (true) {
            $typesToFind = array_merge([T_USE], TokenHelper::$typeKeywordTokenCodes);
            $pointer = TokenHelper::findNext($phpcsFile, $typesToFind, $pointer);
            if ($pointer === null) {
                break;
            }

            $token = $tokens[$pointer];
            if (in_array($token['code'], TokenHelper::$typeKeywordTokenCodes, true)) {
                $pointer = $token['scope_closer'] + 1;
                continue;
            }
            if (self::isAnonymousFunctionUse($phpcsFile, $pointer)) {
                $pointer++;
                continue;
            }
            $pointers[] = $pointer;
            $pointer++;
        }
        return $pointers;
    }

}
