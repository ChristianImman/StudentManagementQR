<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class VLookup extends LookupBase
{
    use ArrayEnabled;

    
    public static function lookup(mixed $lookupValue, $lookupArray, mixed $indexNumber, mixed $notExactMatch = true): mixed
    {
        if (is_array($lookupValue) || is_array($indexNumber)) {
            return self::evaluateArrayArgumentsIgnore([self::class, __FUNCTION__], 1, $lookupValue, $lookupArray, $indexNumber, $notExactMatch);
        }

        $notExactMatch = (bool) ($notExactMatch ?? true);

        try {
            self::validateLookupArray($lookupArray);
            $indexNumber = self::validateIndexLookup($lookupArray, $indexNumber);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $f = array_keys($lookupArray);
        $firstRow = array_pop($f);
        if ((!is_array($lookupArray[$firstRow])) || ($indexNumber > count($lookupArray[$firstRow]))) {
            return ExcelError::REF();
        }
        $columnKeys = array_keys($lookupArray[$firstRow]);
        $returnColumn = $columnKeys[--$indexNumber];
        $firstColumn = array_shift($columnKeys) ?? 1;

        if (!$notExactMatch) {
            
            $callable = [self::class, 'vlookupSort'];
            uasort($lookupArray, $callable);
        }

        $rowNumber = self::vLookupSearch($lookupValue, $lookupArray, $firstColumn, $notExactMatch);

        if ($rowNumber !== null) {
            
            return $lookupArray[$rowNumber][$returnColumn];
        }

        return ExcelError::NA();
    }

    private static function vlookupSort(array $a, array $b): int
    {
        reset($a);
        $firstColumn = key($a);
        $aLower = StringHelper::strToLower((string) $a[$firstColumn]);
        $bLower = StringHelper::strToLower((string) $b[$firstColumn]);

        if ($aLower == $bLower) {
            return 0;
        }

        return ($aLower < $bLower) ? -1 : 1;
    }

    
    private static function vLookupSearch(mixed $lookupValue, array $lookupArray, $column, bool $notExactMatch): ?int
    {
        $lookupLower = StringHelper::strToLower(StringHelper::convertToString($lookupValue));

        $rowNumber = null;
        foreach ($lookupArray as $rowKey => $rowData) {
            $bothNumeric = self::numeric($lookupValue) && self::numeric($rowData[$column]);
            $bothNotNumeric = !self::numeric($lookupValue) && !self::numeric($rowData[$column]);
            $cellDataLower = StringHelper::strToLower((string) $rowData[$column]);

            
            if (
                $notExactMatch
                && (($bothNumeric && ($rowData[$column] > $lookupValue))
                || ($bothNotNumeric && ($cellDataLower > $lookupLower)))
            ) {
                break;
            }

            $rowNumber = self::checkMatch(
                $bothNumeric,
                $bothNotNumeric,
                $notExactMatch,
                $rowKey,
                $cellDataLower,
                $lookupLower,
                $rowNumber
            );
        }

        return $rowNumber;
    }

    private static function numeric(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }
}
