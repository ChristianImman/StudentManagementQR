<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class HLookup extends LookupBase
{
    use ArrayEnabled;

    
    public static function lookup(mixed $lookupValue, $lookupArray, $indexNumber, mixed $notExactMatch = true): mixed
    {
        if (is_array($lookupValue) || is_array($indexNumber)) {
            return self::evaluateArrayArgumentsIgnore([self::class, __FUNCTION__], 1, $lookupValue, $lookupArray, $indexNumber, $notExactMatch);
        }

        $notExactMatch = (bool) ($notExactMatch ?? true);

        try {
            self::validateLookupArray($lookupArray);
            $lookupArray = self::convertLiteralArray($lookupArray);
            $indexNumber = self::validateIndexLookup($lookupArray, $indexNumber);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $f = array_keys($lookupArray);
        $firstRow = reset($f);
        if ((!is_array($lookupArray[$firstRow])) || ($indexNumber > count($lookupArray))) {
            return ExcelError::REF();
        }

        $firstkey = $f[0] - 1;
        $returnColumn = $firstkey + $indexNumber;
        $firstColumn = array_shift($f) ?? 1;
        $rowNumber = self::hLookupSearch($lookupValue, $lookupArray, $firstColumn, $notExactMatch);

        if ($rowNumber !== null) {
            
            return $lookupArray[$returnColumn][Coordinate::stringFromColumnIndex($rowNumber)];
        }

        return ExcelError::NA();
    }

    
    private static function hLookupSearch(mixed $lookupValue, array $lookupArray, $column, bool $notExactMatch): ?int
    {
        $lookupLower = StringHelper::strToLower(StringHelper::convertToString($lookupValue));

        $rowNumber = null;
        foreach ($lookupArray[$column] as $rowKey => $rowData) {
            
            $bothNumeric = is_numeric($lookupValue) && is_numeric($rowData);
            $bothNotNumeric = !is_numeric($lookupValue) && !is_numeric($rowData);
            $cellDataLower = StringHelper::strToLower((string) $rowData);

            if (
                $notExactMatch
                && (($bothNumeric && $rowData > $lookupValue) || ($bothNotNumeric && $cellDataLower > $lookupLower))
            ) {
                break;
            }

            $rowNumber = self::checkMatch(
                $bothNumeric,
                $bothNotNumeric,
                $notExactMatch,
                Coordinate::columnIndexFromString($rowKey),
                $cellDataLower,
                $lookupLower,
                $rowNumber
            );
        }

        return $rowNumber;
    }

    private static function convertLiteralArray(array $lookupArray): array
    {
        if (array_key_exists(0, $lookupArray)) {
            $lookupArray2 = [];
            $row = 0;
            foreach ($lookupArray as $arrayVal) {
                ++$row;
                if (!is_array($arrayVal)) {
                    $arrayVal = [$arrayVal];
                }
                $arrayVal2 = [];
                foreach ($arrayVal as $key2 => $val2) {
                    $index = Coordinate::stringFromColumnIndex($key2 + 1);
                    $arrayVal2[$index] = $val2;
                }
                $lookupArray2[$row] = $arrayVal2;
            }
            $lookupArray = $lookupArray2;
        }

        return $lookupArray;
    }
}
