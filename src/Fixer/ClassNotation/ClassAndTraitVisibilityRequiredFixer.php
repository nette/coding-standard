<?php

declare(strict_types=1);

namespace Nette\CodingStandard\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionMethod;
use SplFileInfo;

final class ClassAndTraitVisibilityRequiredFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
	/**
	 * @var VisibilityRequiredFixer
	 */
	private $visibilityRequiredFixer;


	public function __construct()
	{
		$this->visibilityRequiredFixer = new VisibilityRequiredFixer;
		parent::__construct();
	}


	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isAnyTokenKindsFound([T_CLASS, T_TRAIT]);
	}


	public function getDefinition(): FixerDefinitionInterface
	{
		return $this->visibilityRequiredFixer->getDefinition();
	}


	public function configure(array $configuration = null): void
	{
		$this->configuration = $configuration;
		$this->visibilityRequiredFixer->configure($configuration);
	}


	protected function applyFix(SplFileInfo $file, Tokens $tokens): void
	{
		/**
		 * Hack note: This reflection opening was chosen as more future-proof
		 * than duplicating whole 300-lines class. As "VisibilityRequiredFixer" class is final
		 * and "applyFix()" is final, there is no other way round it.
		 */
		$method = new ReflectionMethod($this->visibilityRequiredFixer, 'applyFix');
		$method->setAccessible(true);
		$method->invoke($this->visibilityRequiredFixer, $file, $tokens);
	}
}
