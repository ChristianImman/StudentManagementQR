<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Percentiles
{
    public const RANK_SORT_DESCENDING = 0;

    public const RANK_SORT_ASCENDING = 1;

    
    public static function PERCENTILE(mixed ...$args)
    {
        $aArgs = Functions::flattenArray($args);

        
        $entry = array_pop($aArgs);

        try {
            $entry = StatisticalValidations::validateFloat($entry);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($entry < 0) || ($entry > 1)) {
            return ExcelError::NAN();
        }

        $mArgs = self::percentileFilterValues($aArgs);
        $mValueCount = count($mArgs);
        if ($mValueCount > 0) {
            sort($mArgs);
            $count = Counts::COUNT($mArgs);
            $index = $entry * ($count - 1);
            $indexFloor = floor($index);
            $iBase = (int) $indexFloor;
            if ($index == $indexFloor) {
                return $mArgs[$iBase];
            }
            $iNext = $iBase + 1;
            $iProportion = $index - $iBase;

            return $mArgs[$iBase] + (($mArgs[$iNext] - $mArgs[$iBase]) * $iProportion);
        }

        return ExcelError::NAN();
    }

    
    public static function PERCENTRANK(mixed $valueSet, mixed $value, mixed $significance = 3): string|float
    {
        $valueSet = Functions::flattenArray($valueSet);
        $value = Functions::flattenSingleValue($value);
        $significance = ($significance === null) ? 3 : Functions::flattenSingleValue($significance);

        try {
            $value = StatisticalValidations::validateFloat($value);
            $significance = StatisticalValidations::validateInt($significance);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $valueSet = self::rankFilterValues($valueSet);
        $valueCount = count($valueSet);
        if ($valueCount == 0) {
            return ExcelError::NA();
        }
        sort($valueSet, SORT_NUMERIC);

        $valueAdjustor = $valueCount - 1;
        if (($value < $valueSet[0]) || ($value > $valueSet[$valueAdjustor])) {
            return ExcelError::NA();
        }

        $pos = array_search($value, $valueSet);
        if ($pos === false) {
            $pos = 0;
            $testValue = $valueSet[0];
            while ($testValue < $value) {
                $testValue = $valueSet[++$pos];
            }
            --$pos;
            $pos += (($value - $valueSet[$pos]) / ($testValue - $valueSet[$pos]));
        }

        return round(((float) $pos) / $valueAdjustor, $significance);
    }

    
    public static function QUARTILE(mixed ...$args)
    {
        $aArgs = Functions::flattenArray($args);
        $entry = array_pop($aArgs);

        try {
            $entry = StatisticalValidations::validateFloat($entry);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $entry = floor($entry);
        $entry /= 4;
        if (($entry < 0) || ($entry > 1)) {
            return ExcelError::NAN();
        }

        return self::PERCENTILE($aArgs, $entry);
    }

    
    public static function RANK(mixed $value, mixed $valueSet, mixed $order = self::RANK_SORT_DESCENDING)
    {
        $value = Functions::flattenSingleValue($value);
        $valueSet = Functions::flattenArray($valueSet);
        $order = ($order === null) ? self::RANK_SORT_DESCENDING : Functions::flattenSingleValue($order);

        try {
            $value = StatisticalValidations::validateFloat($value);
            $order = StatisticalValidations::validateInt($order);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $valueSet = self::rankFilterValues($valueSet);
        if ($order === self::RANK_SORT_DESCENDING) {
            rsort($valueSet, SORT_NUMERIC);
        } else {
            sort($valueSet, SORT_NUMERIC);
        }

        $pos = array_search($value, $valueSet);
        if ($pos === false) {
            return ExcelError::NA();
        }

        return ++$pos;
    }

    protected static function percentileFilterValues(array $dataSet): array
    {
        return array_filter(
            $dataSet,
            fn ($value): bool => is_numeric($value) && !is_string($value)
        );
    }

    protected static function rankFilterValues(array $dataSet): array
    {
        return array_filter(
            $dataSet,
            fn ($value): bool => is_numeric($value)
        );
    }
}
