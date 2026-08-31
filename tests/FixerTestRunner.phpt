<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


/**
 * This runner creates a temporary, isolated PHP CS Fixer config to ensure only the target fixers are tested.
 */
class FixerTestRunner
{
	private string $fixerPath;
	private string $testsDir;
	private array $tempFiles = [];


	public function __construct()
	{
		$this->fixerPath = realpath(__DIR__ . '/../vendor/bin/php-cs-fixer');
		$this->testsDir = __DIR__ . '/fixtures.fixer';

		if (!$this->fixerPath || !file_exists($this->fixerPath)) {
			throw new Exception("php-cs-fixer executable not found at: {$this->fixerPath}");
		}
	}


	public function __destruct()
	{
		foreach ($this->tempFiles as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}


	/**
	 * Creates a temporary config registering the custom fixers, with only the given rules enabled.
	 */
	private function createConfig(array $rules, string $inputFile): string
	{
		$php = '<?php declare(strict_types=1);' . PHP_EOL;
		$php .= 'require ' . var_export(realpath(__DIR__ . '/../vendor/autoload.php'), true) . ';' . PHP_EOL;
		$php .= '$config = new PhpCsFixer\Config;' . PHP_EOL;
		$php .= '$config->registerCustomFixers([' . PHP_EOL;
		$php .= '	new NetteCodingStandard\Fixer\Whitespace\StatementIndentationFixer,' . PHP_EOL;
		$php .= '	new NetteCodingStandard\Fixer\Basic\BracesPositionFixer,' . PHP_EOL;
		$php .= '	new NetteCodingStandard\Fixer\ClassNotation\ClassAndTraitVisibilityRequiredFixer,' . PHP_EOL;
		$php .= '	new NetteCodingStandard\Fixer\FunctionNotation\MethodArgumentSpaceFixer,' . PHP_EOL;
		$php .= '	new NetteCodingStandard\Fixer\Import\NoLeadingSlashInGlobalNamespaceFixer,' . PHP_EOL;
		$php .= '	new NetteCodingStandard\Fixer\Import\OrderedImportsFixer,' . PHP_EOL;
		$php .= ']);' . PHP_EOL;
		$php .= '$config->setUsingCache(false);' . PHP_EOL;
		$php .= '$config->setIndent("\t");' . PHP_EOL;
		$php .= '$config->setRiskyAllowed(true);' . PHP_EOL;
		$php .= '$config->setRules(' . var_export($rules, true) . ');' . PHP_EOL;
		$php .= '$config->setFinder([new SplFileInfo(' . var_export($inputFile, true) . ')]);' . PHP_EOL;
		$php .= 'return $config;' . PHP_EOL;

		$path = sys_get_temp_dir() . '/fixer_config_' . uniqid() . '.php';
		file_put_contents($path, $php);
		$this->tempFiles[] = $path;
		return $path;
	}


	/**
	 * Discovers and runs all test cases from the fixtures directory.
	 */
	public function run(): void
	{
		$testFiles = glob($this->testsDir . '/*.inc');

		foreach ($testFiles as $testFile) {
			test(basename($testFile, '.inc'), function () use ($testFile) {
				$this->runTestFromFile($testFile);
			});
		}
	}


	/**
	 * Runs a single test case.
	 */
	private function runTestFromFile(string $inputFile): void
	{
		$inputCode = file_get_contents($inputFile);

		$expectedFile = $inputFile . '.expected';
		$expectedFixedCode = file_exists($expectedFile) ? file_get_contents($expectedFile) : $inputCode;

		$actualFixedContent = $this->getFixedContent($inputCode);

		$expectedFixedCode = str_replace("\r\n", "\n", $expectedFixedCode);
		$actualFixedContent = str_replace("\r\n", "\n", $actualFixedContent);

		Assert::same($expectedFixedCode, $actualFixedContent, 'Mismatch for ' . basename($inputFile));
	}


	/**
	 * Executes php-cs-fixer with the isolated config and returns the fixed content.
	 */
	private function getFixedContent(string $inputCode): string
	{
		$tempInputFile = sys_get_temp_dir() . '/fixer_test_input_' . uniqid() . '.php';
		file_put_contents($tempInputFile, $inputCode);
		$this->tempFiles[] = $tempInputFile;

		$rules = $this->parseRulesFromJsonComment($inputCode);
		$configPath = $this->createConfig($rules, $tempInputFile);

		$command = escapeshellarg(phpCliBinary()) . ' ' . escapeshellarg($this->fixerPath);
		$command .= ' fix --config=' . escapeshellarg($configPath);

		$descriptorSpec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$process = proc_open($command, $descriptorSpec, $pipes);
		$output = '';
		$errors = '';
		$exitCode = -1;

		if (is_resource($process)) {
			$output = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			$errors = stream_get_contents($pipes[2]);
			fclose($pipes[2]);
			$exitCode = proc_close($process);
		}

		if ($exitCode !== 0) {
			return "PHP-CS-FIXER FAILED WITH EXIT CODE {$exitCode}:\n" . $output . $errors;
		}

		return file_get_contents($tempInputFile);
	}


	/**
	 * Parses a special JSON comment on the first line of the code into the rules array.
	 * E.g., <?php // {"Nette/no_leading_slash_in_global_namespace": true}
	 */
	private function parseRulesFromJsonComment(string $code): array
	{
		if (!preg_match('~^\s*<\?php\s*//\s*({.+})~', $code, $matches)) {
			throw new Exception('Missing the JSON comment with rules on the first line.');
		}

		return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
	}
}


(new FixerTestRunner)->run();
