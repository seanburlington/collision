<?php

declare(strict_types=1);

namespace NunoMaduro\Collision\Adapters\Phpunit;

use NunoMaduro\Collision\Contracts\Adapters\Phpunit\HasPrintableTestCaseName;
use NunoMaduro\Collision\Exceptions\ShouldNotHappen;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;

/**
 * @internal
 */
final class TestResult
{
    public const FAIL = 'failed';

    public const SKIPPED = 'skipped';

    public const INCOMPLETE = 'incompleted';

    public const RISKY = 'risky';

    public const DEPRECATED = 'deprecated';

    public const WARN = 'warnings';

    public const RUNS = 'pending';

    public const PASS = 'passed';

    public string $id;

    public string $testCaseName;

    public string $description;

    public string $type;

    public string $icon;

    public string $color;

    public ?Throwable $throwable;

    public string $warning = '';

    /**
     * Creates a new TestResult instance.
     */
    private function __construct(string $id, string $testCaseName, string $description, string $type, string $icon, string $color, Throwable $throwable = null)
    {
        $this->id = $id;
        $this->testCaseName = $testCaseName;
        $this->description = $description;
        $this->type = $type;
        $this->icon = $icon;
        $this->color = $color;
        $this->throwable = $throwable;

        $asWarning = $this->type === TestResult::WARN
             || $this->type === TestResult::RISKY
             || $this->type === TestResult::SKIPPED
             || $this->type === TestResult::DEPRECATED
             || $this->type === TestResult::INCOMPLETE;

        if ($throwable instanceof Throwable && $asWarning) {
            $this->warning = trim((string) preg_replace("/\r|\n/", ' ', $throwable->message()));
        }
    }

    /**
     * Creates a new test from the given test case.
     */
    public static function fromTestCase(Test $test, string $type, Throwable $throwable = null): self
    {
        if (! $test instanceof TestMethod) {
            throw new ShouldNotHappen();
        }

        if (is_subclass_of($test->className(), HasPrintableTestCaseName::class)) {
            $testCaseName = (new ($test->className())($test->name()))->getPrintableTestCaseName();
        } else {
            $testCaseName = $test->className();
        }

        $description = self::makeDescription($test);

        $icon = self::makeIcon($type);

        $color = self::makeColor($type);

        return new self($test->id(), $testCaseName, $description, $type, $icon, $color, $throwable);
    }

    /**
     * Get the test case description.
     */
    public static function makeDescription(TestMethod $test): string
    {
        if (is_subclass_of($test->className(), HasPrintableTestCaseName::class)) {
            return (new ($test->className())($test->name()))->getPrintableTestCaseMethodName();
        }

        $name = $test->name();

        // First, lets replace underscore by spaces.
        $name = str_replace('_', ' ', $name);

        // Then, replace upper cases by spaces.
        $name = (string) preg_replace('/([A-Z])/', ' $1', $name);

        // Finally, if it starts with `test`, we remove it.
        $name = (string) preg_replace('/^test/', '', $name);

        // Removes spaces
        $name = trim($name);

        // Lower case everything
        $name = mb_strtolower($name);

        // Add the dataset name if it has one
        if ($test->testData()->hasDataFromDataProvider()) {
            if ($dataName = $test->testData()->dataFromDataProvider()->dataSetName()) {
                if (is_int($dataName)) {
                    $name .= sprintf(' with data set #%d', $dataName);
                } else {
                    $name .= sprintf(' with data set "%s"', $dataName);
                }
            }
        }

        return $name;
    }

    /**
     * Get the test case icon.
     */
    public static function makeIcon(string $type): string
    {
        switch ($type) {
            case self::DEPRECATED:
                return 'd';
            case self::FAIL:
                return '⨯';
            case self::SKIPPED:
                return '-';
            case self::WARN:
            case self::RISKY:
                return '!';
            case self::INCOMPLETE:
                return '…';
            case self::RUNS:
                return '•';
            default:
                return '✓';
        }
    }

    /**
     * Get the test case color.
     */
    public static function makeColor(string $type): string
    {
        switch ($type) {
            case self::FAIL:
                return 'red';
            case self::DEPRECATED:
            case self::SKIPPED:
            case self::INCOMPLETE:
            case self::RISKY:
            case self::WARN:
            case self::RUNS:
                return 'yellow';
            default:
                return 'green';
        }
    }
}
