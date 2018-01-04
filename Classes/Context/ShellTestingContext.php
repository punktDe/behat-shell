<?php
namespace PunktDe\Testing\Shell\Context;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

/**
 * Shell Testing context
 */
class ShellTestingContext implements \Behat\Behat\Context\Context
{
    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var string
     */
    protected $returnValue;

    /**
     * @param $workingDirectory
     */
    public function __construct($workingDirectory = '')
    {
        $this->workingDirectory = realpath($workingDirectory);
    }

    /**
     * @When /^I run "([^"]*)"(?: in "([^"]*)"(?: with expected return value "([^"]*)")?)?$/
     */
    public function iRun($command, $relativeWorkingDirectory = '', $expectedReturnValue = 0)
    {
        $absoluteCommandPath = $this->resolvePath($relativeWorkingDirectory);
        $command = $command . ' 2>&1';

        chdir($absoluteCommandPath);
        exec($command, $this->output, $this->returnValue);

        $this->assertReturnValue($command, $absoluteCommandPath, $expectedReturnValue);
    }

    /**
     * @Then I should get
     */
    public function iShouldGet(PyStringNode $string)
    {
        $output = trim(implode(PHP_EOL, $this->output));

        if (trim((string) $string) !== $output) {
            throw new \Exception("Actual output is:" . PHP_EOL . $output, 1408098285);
        }
    }

    /**
     * @Then the output should contain
     */
    public function theOutputShouldContain(PyStringNode $string)
    {
        $output = trim(implode(PHP_EOL, $this->output));

        if (stristr($output, (string) $string) === false) {
            throw new \Exception("Actual output is:" . PHP_EOL . $output, 1408098285);
        }
    }

    /**
     * @Given changes to directory :directory are discarded by git
     */
    public function changesToDirectoryAreDiscardedByGit($directory)
    {
        $absoluteDirectoryPath = $this->resolvePath($directory);

        Files::emptyDirectoryRecursively($absoluteDirectoryPath);

        $command = 'git checkout -- ' . $absoluteDirectoryPath . ' 2>&1';

        exec($command, $this->output, $this->returnValue);

        $this->assertReturnValue($command, $absoluteDirectoryPath, 0);
    }

    /**
     * @Then the file :filePath exists
     */
    public function theFileExists($filePath)
    {
        $absoluteFilePath = $this->resolvePath($filePath);

        if (!is_file($absoluteFilePath)) {
            throw new \Exception(sprintf('The file %s does not exist.', $absoluteFilePath));
        }
    }
    
    /**
     * @Then the file count in :path should be :fileCount
     */
    public function theFileCountInShouldBe($path, $fileCount)
    {
        $directoryContent = Files::readDirectoryRecursively(Files::concatenatePaths(array($this->workingDirectory, $path)));

        Assert::assertEquals($fileCount, count($directoryContent));
    }
    
    /**
     * @param $relativePath
     * @return string
     * @throws \Exception
     */
    protected function resolvePath(string $relativePath): string
    {
        $absolutePath = Files::concatenatePaths(array(
            $this->workingDirectory,
            $relativePath,
        ));

        if (!(is_dir($absolutePath) || is_file($absolutePath))) {
            throw new \Exception(sprintf('The directory %s was not found.', $absolutePath), 1515050360);
        }

        return $absolutePath;
    }

    /**
     * @param string $command
     * @param string $absoluteCommandPath
     * @param integer $expectedReturnValue
     *
     * @throws \Exception
     */
    protected function assertReturnValue(string $command, string $absoluteCommandPath, int $expectedReturnValue)
    {
        if ((int)$this->returnValue !== (int)$expectedReturnValue) {
            throw new \Exception(sprintf("The command %s, executed in %s exited with status:\n--\n%s\n--\n and returned \n--\n%s\n--\n ", $command, $absoluteCommandPath, $this->returnValue, implode("\n", $this->output)), 1408098286);
        }
    }
}
