<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Database\DAverage;
use PhpOffice\PhpSpreadsheet\Calculation\Database\DCount;
use PhpOffice\PhpSpreadsheet\Calculation\Database\DMax;
use PhpOffice\PhpSpreadsheet\Calculation\Database\DMin;
use PhpOffice\PhpSpreadsheet\Calculation\Database\DSum;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcException;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Conditional
{
    private const CONDITION_COLUMN_NAME = 'CONDITION';
    private const VALUE_COLUMN_NAME = 'VALUE';
    private const CONDITIONAL_COLUMN_NAME = 'CONDITIONAL %d';

    
    public static function AVERAGEIF(mixed $range, null|array|string $condition, mixed $averageRange = []): null|int|float|string
    {
        if (!is_array($range) || !is_array($averageRange) || array_key_exists(0, $range) || array_key_exists(0, $averageRange)) {
            $refError = ExcelError::REF();
            if (in_array($refError, [$range, $averageRange], true)) {
                return $refError;
            }

            throw new CalcException('Must specify range of cells, not any kind of literal');
        }
        $database = self::databaseFromRangeAndValue($range, $averageRange);
        $condition = [[self::CONDITION_COLUMN_NAME, self::VALUE_COLUMN_NAME], [$condition, null]];

        return DAverage::evaluate($database, self::VALUE_COLUMN_NAME, $condition);
    }

    
    public static function AVERAGEIFS(mixed ...$args): null|int|float|string
    {
        if (empty($args)) {
            return 0.0;
        }
        if (count($args) === 3) {
            return self::AVERAGEIF($args[1], $args[2], $args[0]); /
    public static function COUNTIF(mixed $range, null|array|string $condition): string|int
    {
        if (
            !is_array($range)
            || array_key_exists(0, $range)
        ) {
            if ($range === ExcelError::REF()) {
                return $range;
            }

            throw new CalcException('Must specify range of cells, not any kind of literal');
        }
        
        $range = array_filter(
            Functions::flattenArray($range),
            fn ($value): bool => $value !== null && $value !== ''
        );

        $range = array_merge([[self::CONDITION_COLUMN_NAME]], array_chunk($range, 1));
        $condition = array_merge([[self::CONDITION_COLUMN_NAME]], [[$condition]]);

        return DCount::evaluate($range, null, $condition, false);
    }

    
    public static function COUNTIFS(mixed ...$args): int|string
    {
        if (empty($args)) {
            return 0;
        } elseif (count($args) === 2) {
            return self::COUNTIF(...$args);
        }

        $database = self::buildDatabase(...$args);
        $conditions = self::buildConditionSet(...$args);

        return DCount::evaluate($database, null, $conditions, false);
    }

    
    public static function MAXIFS(mixed ...$args): null|float|string
    {
        if (empty($args)) {
            return 0.0;
        }

        $conditions = self::buildConditionSetForValueRange(...$args);
        $database = self::buildDatabaseWithValueRange(...$args);

        return DMax::evaluate($database, self::VALUE_COLUMN_NAME, $conditions, false);
    }

    
    public static function MINIFS(mixed ...$args): null|float|string
    {
        if (empty($args)) {
            return 0.0;
        }

        $conditions = self::buildConditionSetForValueRange(...$args);
        $database = self::buildDatabaseWithValueRange(...$args);

        return DMin::evaluate($database, self::VALUE_COLUMN_NAME, $conditions, false);
    }

    
    public static function SUMIF(mixed $range, mixed $condition, mixed $sumRange = []): null|float|string
    {
        if (
            !is_array($range)
            || array_key_exists(0, $range)
            || !is_array($sumRange)
            || array_key_exists(0, $sumRange)
        ) {
            $refError = ExcelError::REF();
            if (in_array($refError, [$range, $sumRange], true)) {
                return $refError;
            }

            throw new CalcException('Must specify range of cells, not any kind of literal');
        }
        $database = self::databaseFromRangeAndValue($range, $sumRange);
        $condition = [[self::CONDITION_COLUMN_NAME, self::VALUE_COLUMN_NAME], [$condition, null]];

        return DSum::evaluate($database, self::VALUE_COLUMN_NAME, $condition);
    }

    
    public static function SUMIFS(mixed ...$args): null|float|string
    {
        if (empty($args)) {
            return 0.0;
        } elseif (count($args) === 3) {
            return self::SUMIF($args[1], $args[2], $args[0]);
        }

        $conditions = self::buildConditionSetForValueRange(...$args);
        $database = self::buildDatabaseWithValueRange(...$args);

        return DSum::evaluate($database, self::VALUE_COLUMN_NAME, $conditions);
    }

    
    private static function buildConditionSet(...$args): array
    {
        $conditions = self::buildConditions(1, ...$args);

        return array_map(null, ...$conditions);
    }

    
    private static function buildConditionSetForValueRange(...$args): array
    {
        $conditions = self::buildConditions(2, ...$args);

        if (count($conditions) === 1) {
            return array_map(
                fn ($value): array => [$value],
                $conditions[0]
            );
        }

        return array_map(null, ...$conditions);
    }

    
    private static function buildConditions(int $startOffset, ...$args): array
    {
        $conditions = [];

        $pairCount = 1;
        $argumentCount = count($args);
        for ($argument = $startOffset; $argument < $argumentCount; $argument += 2) {
            $conditions[] = array_merge([sprintf(self::CONDITIONAL_COLUMN_NAME, $pairCount)], [$args[$argument]]);
            ++$pairCount;
        }

        return $conditions;
    }

    
    private static function buildDatabase(...$args): array
    {
        $database = [];

        return self::buildDataSet(0, $database, ...$args);
    }

    
    private static function buildDatabaseWithValueRange(...$args): array
    {
        $database = [];
        $database[] = array_merge(
            [self::VALUE_COLUMN_NAME],
            Functions::flattenArray($args[0])
        );

        return self::buildDataSet(1, $database, ...$args);
    }

    
    private static function buildDataSet(int $startOffset, array $database, ...$args): array
    {
        $pairCount = 1;
        $argumentCount = count($args);
        for ($argument = $startOffset; $argument < $argumentCount; $argument += 2) {
            $database[] = array_merge(
                [sprintf(self::CONDITIONAL_COLUMN_NAME, $pairCount)],
                Functions::flattenArray($args[$argument])
            );
            ++$pairCount;
        }

        return array_map(null, ...$database);
    }

    private static function databaseFromRangeAndValue(array $range, array $valueRange = []): array
    {
        $range = Functions::flattenArray($range);

        $valueRange = Functions::flattenArray($valueRange);
        if (empty($valueRange)) {
            $valueRange = $range;
        }

        $database = array_map(null, array_merge([self::CONDITION_COLUMN_NAME], $range), array_merge([self::VALUE_COLUMN_NAME], $valueRange));

        return $database;
    }
}
