<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


/**
 * This runner creates a temporary, isolated ruleset to ensure only the target sniff is tested.
 */
class SniffTestRunner
{
	private $sniffPath;
	private $phpcbfPath;
	private $testsDir;
	private $tempRulesetPath;


	public function __construct()
	{
		$this->sniffPath = realpath(__DIR__ . '/../src/NetteCodingStandard/Sniffs/Namespaces/OptimizeGlobalCallsSniff.php');
		$this->phpcbfPath = realpath(__DIR__ . '/../vendor/bin/phpcbf');
		$this->testsDir = __DIR__ . '/fixtures';

		if (!$this->sniffPath || !file_exists($this->sniffPath)) {
			throw new Exception("FATAL ERROR: Sniff file not found at: {$this->sniffPath}");
		}
		if (!$this->phpcbfPath || !file_exists($this->phpcbfPath)) {
			throw new Exception("FATAL ERROR: phpcbf executable not found at: {$this->phpcbfPath}");
		}

		$this->createTempRuleset();
	}


	public function __destruct()
	{
		if ($this->tempRulesetPath && file_exists($this->tempRulesetPath)) {
			unlink($this->tempRulesetPath);
		}
	}


	/**
	 * Creates a temporary ruleset XML file that references only the sniff under test.
	 */
	private function createTempRuleset(): void
	{
		$ruleset = '<?xml version="1.0"?>' . PHP_EOL;
		$ruleset .= '<ruleset name="IsolatedSniffTest">' . PHP_EOL;
		$ruleset .= '    <description>A temporary ruleset to test a single sniff in isolation.</description>' . PHP_EOL;
		$ruleset .= '    <rule ref="' . htmlspecialchars($this->sniffPath, ENT_XML1) . '"/>' . PHP_EOL;
		$ruleset .= '</ruleset>' . PHP_EOL;

		$this->tempRulesetPath = sys_get_temp_dir() . '/phpcs_temp_ruleset_' . uniqid() . '.xml';
		file_put_contents($this->tempRulesetPath, $ruleset);
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
	 * Executes phpcbf with the isolated ruleset and returns the fixed content.
	 * Writes to a temporary file to avoid issues with PHP CGI headers.
	 * If phpcbf fails, it returns the content of STDERR instead.
	 */
	private function getFixedContent(string $inputCode): string
	{
		$tempInputFile = sys_get_temp_dir() . '/phpcs_test_input_' . uniqid() . '.php';
		file_put_contents($tempInputFile, $inputCode);

		$properties = $this->parsePropertiesFromJsonComment($inputCode);
		$sniffCode = 'NetteCodingStandard.Namespaces.OptimizeGlobalCalls';

		$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->phpcbfPath);
		$command .= ' --standard=' . escapeshellarg($this->tempRulesetPath);
		$command .= ' --no-cache';
		$command .= ' ' . escapeshellarg($tempInputFile); // Process the temp file

		foreach ($properties as $key => $value) {
			$valueString = match (gettype($value)) {
				'array'   => implode(',', $value),
				'boolean' => $value ? 'true' : 'false',
				default   => (string) $value,
			};
			$command .= ' --runtime-set ' . escapeshellarg($sniffCode . '.' . $key) . ' ' . escapeshellarg($valueString);
		}

		$descriptorSpec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$process = proc_open($command, $descriptorSpec, $pipes);
		$fixedContent = '';
		$errors = '';
		$exitCode = -1;

		if (is_resource($process)) {
			// We don't need stdout as phpcbf modifies the file in place
			fclose($pipes[1]);

			$errors = stream_get_contents($pipes[2]);
			fclose($pipes[2]);

			$exitCode = proc_close($process);
		}

		if ($exitCode !== 0) {
			// If phpcbf failed, return the error output for easier debugging
			unlink($tempInputFile);
			return "PHPCBF FAILED WITH EXIT CODE {$exitCode}:\n" . $errors;
		}

		$fixedContent = file_get_contents($tempInputFile);
		unlink($tempInputFile);

		return $fixedContent;
	}


	/**
	 * Parses a special JSON comment on the first line of the code to set sniff properties.
	 * E.g., <?php // {"optimizedFunctionsOnly": true, "ignoredFunctions": ["dd"]}
	 */
	private function parsePropertiesFromJsonComment(string $code): array
	{
		return preg_match('~^\s*<\?php\s*//\s*({.+})~', $code, $matches)
			? json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR)
			: [];
	}
}


(new SniffTestRunner)->run();
