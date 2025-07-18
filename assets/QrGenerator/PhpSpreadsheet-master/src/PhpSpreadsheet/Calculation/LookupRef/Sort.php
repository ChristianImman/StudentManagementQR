<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Sort extends LookupRefValidations
{
    public const ORDER_ASCENDING = 1;
    public const ORDER_DESCENDING = -1;

    
    public static function sort(mixed $sortArray, mixed $sortIndex = 1, mixed $sortOrder = self::ORDER_ASCENDING, mixed $byColumn = false): mixed
    {
        if (!is_array($sortArray)) {
            
            return $sortArray;
        }

        $sortArray = self::enumerateArrayKeys($sortArray);

        $byColumn = (bool) $byColumn;
        $lookupIndexSize = $byColumn ? count($sortArray) : count($sortArray[0]);

        try {
            
            if (!is_array($sortIndex)) {
                $sortIndex = [$sortIndex];
                $sortOrder = is_scalar($sortOrder) ? [$sortOrder] : $sortOrder;
            }
            
            $sortOrder = (empty($sortOrder) ? [self::ORDER_ASCENDING] : $sortOrder);
            self::validateArrayArgumentsForSort($sortIndex, $sortOrder, $lookupIndexSize);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        

        
        $sortArray = array_values(array_map('array_values', $sortArray));

        return ($byColumn === true)
            ? self::sortByColumn($sortArray, $sortIndex, $sortOrder)
            : self::sortByRow($sortArray, $sortIndex, $sortOrder);
    }

    
    public static function sortBy(mixed $sortArray, mixed ...$args): mixed
    {
        if (!is_array($sortArray)) {
            
            return $sortArray;
        }

        $sortArray = self::enumerateArrayKeys($sortArray);

        $lookupArraySize = count($sortArray);
        $argumentCount = count($args);

        try {
            $sortBy = $sortOrder = [];
            for ($i = 0; $i < $argumentCount; $i += 2) {
                $sortBy[] = self::validateSortVector($args[$i], $lookupArraySize);
                $sortOrder[] = self::validateSortOrder($args[$i + 1] ?? self::ORDER_ASCENDING);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::processSortBy($sortArray, $sortBy, $sortOrder);
    }

    private static function enumerateArrayKeys(array $sortArray): array
    {
        array_walk(
            $sortArray,
            function (&$columns): void {
                if (is_array($columns)) {
                    $columns = array_values($columns);
                }
            }
        );

        return array_values($sortArray);
    }

    private static function validateScalarArgumentsForSort(mixed &$sortIndex, mixed &$sortOrder, int $sortArraySize): void
    {
        if (is_array($sortIndex) || is_array($sortOrder)) {
            throw new Exception(ExcelError::VALUE());
        }

        $sortIndex = self::validatePositiveInt($sortIndex, false);

        if ($sortIndex > $sortArraySize) {
            throw new Exception(ExcelError::VALUE());
        }

        $sortOrder = self::validateSortOrder($sortOrder);
    }

    private static function validateSortVector(mixed $sortVector, int $sortArraySize): array
    {
        if (!is_array($sortVector)) {
            throw new Exception(ExcelError::VALUE());
        }

        
        $sortVector = Functions::flattenArray($sortVector);
        if (count($sortVector) !== $sortArraySize) {
            throw new Exception(ExcelError::VALUE());
        }

        return $sortVector;
    }

    private static function validateSortOrder(mixed $sortOrder): int
    {
        $sortOrder = self::validateInt($sortOrder);
        if (($sortOrder == self::ORDER_ASCENDING || $sortOrder === self::ORDER_DESCENDING) === false) {
            throw new Exception(ExcelError::VALUE());
        }

        return $sortOrder;
    }

    private static function validateArrayArgumentsForSort(array &$sortIndex, mixed &$sortOrder, int $sortArraySize): void
    {
        
        $sortIndex = Functions::flattenArray($sortIndex);
        $sortOrder = Functions::flattenArray($sortOrder);

        if (
            count($sortOrder) === 0 || count($sortOrder) > $sortArraySize
            || (count($sortOrder) > count($sortIndex))
        ) {
            throw new Exception(ExcelError::VALUE());
        }

        if (count($sortIndex) > count($sortOrder)) {
            
            $sortOrder = array_merge(
                $sortOrder,
                array_fill(0, count($sortIndex) - count($sortOrder), array_pop($sortOrder))
            );
        }

        foreach ($sortIndex as $key => &$value) {
            self::validateScalarArgumentsForSort($value, $sortOrder[$key], $sortArraySize);
        }
    }

    private static function prepareSortVectorValues(array $sortVector): array
    {
        
        return array_map(
            function ($value) {
                if (is_bool($value)) {
                    return ($value) ? Calculation::getTRUE() : Calculation::getFALSE();
                } elseif (is_string($value)) {
                    return StringHelper::strToLower($value);
                }

                return $value;
            },
            $sortVector
        );
    }

    
    private static function processSortBy(array $sortArray, array $sortIndex, array $sortOrder): array
    {
        $sortArguments = [];
        $sortData = [];
        foreach ($sortIndex as $index => $sortValues) {
            $sortData[] = $sortValues;
            $sortArguments[] = self::prepareSortVectorValues($sortValues);
            $sortArguments[] = $sortOrder[$index] === self::ORDER_ASCENDING ? SORT_ASC : SORT_DESC;
        }

        $sortVector = self::executeVectorSortQuery($sortData, $sortArguments);

        return self::sortLookupArrayFromVector($sortArray, $sortVector);
    }

    
    private static function sortByRow(array $sortArray, array $sortIndex, array $sortOrder): array
    {
        $sortVector = self::buildVectorForSort($sortArray, $sortIndex, $sortOrder);

        return self::sortLookupArrayFromVector($sortArray, $sortVector);
    }

    
    private static function sortByColumn(array $sortArray, array $sortIndex, array $sortOrder): array
    {
        $sortArray = Matrix::transpose($sortArray);
        $result = self::sortByRow($sortArray, $sortIndex, $sortOrder);

        return Matrix::transpose($result);
    }

    
    private static function buildVectorForSort(array $sortArray, array $sortIndex, array $sortOrder): array
    {
        $sortArguments = [];
        $sortData = [];
        foreach ($sortIndex as $index => $sortIndexValue) {
            $sortValues = array_column($sortArray, $sortIndexValue - 1);
            $sortData[] = $sortValues;
            $sortArguments[] = self::prepareSortVectorValues($sortValues);
            $sortArguments[] = $sortOrder[$index] === self::ORDER_ASCENDING ? SORT_ASC : SORT_DESC;
        }

        $sortData = self::executeVectorSortQuery($sortData, $sortArguments);

        return $sortData;
    }

    private static function executeVectorSortQuery(array $sortData, array $sortArguments): array
    {
        $sortData = Matrix::transpose($sortData);

        
        $sortDataIndexed = [];
        foreach ($sortData as $key => $value) {
            $sortDataIndexed[Coordinate::stringFromColumnIndex($key + 1)] = $value;
        }
        unset($sortData);

        $sortArguments[] = &$sortDataIndexed;

        array_multisort(...$sortArguments);

        
        $sortedData = [];
        foreach (array_keys($sortDataIndexed) as $key) {
            $sortedData[] = Coordinate::columnIndexFromString($key) - 1;
        }

        return $sortedData;
    }

    private static function sortLookupArrayFromVector(array $sortArray, array $sortVector): array
    {
        
        $sortedArray = [];
        foreach ($sortVector as $index) {
            $sortedArray[] = $sortArray[$index];
        }

        return $sortedArray;









    }
}
