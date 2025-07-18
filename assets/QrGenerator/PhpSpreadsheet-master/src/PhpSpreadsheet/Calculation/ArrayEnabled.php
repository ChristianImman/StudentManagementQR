<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

use PhpOffice\PhpSpreadsheet\Calculation\Engine\ArrayArgumentHelper;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\ArrayArgumentProcessor;

trait ArrayEnabled
{
    private static bool $initializationNeeded = true;

    private static ArrayArgumentHelper $arrayArgumentHelper;

    
    private static function initialiseHelper(array $arguments): void
    {
        if (self::$initializationNeeded === true) {
            self::$arrayArgumentHelper = new ArrayArgumentHelper();
            self::$initializationNeeded = false;
        }
        self::$arrayArgumentHelper->initialise($arguments);
    }

    
    protected static function evaluateSingleArgumentArray(callable $method, array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $result[] = $method($value);
        }

        return $result;
    }

    
    protected static function evaluateArrayArguments(callable $method, mixed ...$arguments): array
    {
        self::initialiseHelper($arguments);
        $arguments = self::$arrayArgumentHelper->arguments();

        return ArrayArgumentProcessor::processArguments(self::$arrayArgumentHelper, $method, ...$arguments);
    }

    
    protected static function evaluateArrayArgumentsSubset(callable $method, int $limit, mixed ...$arguments): array
    {
        self::initialiseHelper(array_slice($arguments, 0, $limit));
        $trailingArguments = array_slice($arguments, $limit);
        $arguments = self::$arrayArgumentHelper->arguments();
        $arguments = array_merge($arguments, $trailingArguments);

        return ArrayArgumentProcessor::processArguments(self::$arrayArgumentHelper, $method, ...$arguments);
    }

    private static function testFalse(mixed $value): bool
    {
        return $value === false;
    }

    
    protected static function evaluateArrayArgumentsSubsetFrom(callable $method, int $start, mixed ...$arguments): array
    {
        $arrayArgumentsSubset = array_combine(
            range($start, count($arguments) - $start),
            array_slice($arguments, $start)
        );
        if (self::testFalse($arrayArgumentsSubset)) {
            return ['
        }

        self::initialiseHelper($arrayArgumentsSubset);
        $leadingArguments = array_slice($arguments, 0, $start);
        $arguments = self::$arrayArgumentHelper->arguments();
        $arguments = array_merge($leadingArguments, $arguments);

        return ArrayArgumentProcessor::processArguments(self::$arrayArgumentHelper, $method, ...$arguments);
    }

    
    protected static function evaluateArrayArgumentsIgnore(callable $method, int $ignore, mixed ...$arguments): array
    {
        $leadingArguments = array_slice($arguments, 0, $ignore);
        $ignoreArgument = array_slice($arguments, $ignore, 1);
        $trailingArguments = array_slice($arguments, $ignore + 1);

        self::initialiseHelper(array_merge($leadingArguments, [[null]], $trailingArguments));
        $arguments = self::$arrayArgumentHelper->arguments();

        array_splice($arguments, $ignore, 1, $ignoreArgument);

        return ArrayArgumentProcessor::processArguments(self::$arrayArgumentHelper, $method, ...$arguments);
    }
}
