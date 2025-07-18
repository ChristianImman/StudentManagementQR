<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Unique
{
    
    public static function unique(mixed $lookupVector, mixed $byColumn = false, mixed $exactlyOnce = false): mixed
    {
        if (!is_array($lookupVector)) {
            
            return $lookupVector;
        }

        $byColumn = (bool) $byColumn;
        $exactlyOnce = (bool) $exactlyOnce;

        return ($byColumn === true)
            ? self::uniqueByColumn($lookupVector, $exactlyOnce)
            : self::uniqueByRow($lookupVector, $exactlyOnce);
    }

    private static function uniqueByRow(array $lookupVector, bool $exactlyOnce): mixed
    {
        
        
        array_walk(
            $lookupVector,
            function (array &$value): void {
                $valuex = '';
                $separator = '';
                $numericIndicator = "\x01";
                foreach ($value as $cellValue) {
                    $valuex .= $separator . $cellValue;
                    $separator = "\x00";
                    if (is_int($cellValue) || is_float($cellValue)) {
                        $valuex .= $numericIndicator;
                    }
                }
                $value = $valuex;
            }
        );

        $result = self::countValuesCaseInsensitive($lookupVector);

        if ($exactlyOnce === true) {
            $result = self::exactlyOnceFilter($result);
        }

        if (count($result) === 0) {
            return ExcelError::CALC();
        }

        $result = array_keys($result);

        
        array_walk(
            $result,
            function (string &$value): void {
                $value = explode("\x00", $value);
                foreach ($value as &$stringValue) {
                    if (str_ends_with($stringValue, "\x01")) {
                        
                        
                        $stringValue = 0 + substr($stringValue, 0, -1); 
                    }
                }
            }
        );

        return (count($result) === 1) ? array_pop($result) : $result;
    }

    private static function uniqueByColumn(array $lookupVector, bool $exactlyOnce): mixed
    {
        $flattenedLookupVector = Functions::flattenArray($lookupVector);

        if (count($lookupVector, COUNT_RECURSIVE) > count($flattenedLookupVector, COUNT_RECURSIVE) + 1) {
            
            $transpose = Matrix::transpose($lookupVector);
            $result = self::uniqueByRow($transpose, $exactlyOnce);

            return (is_array($result)) ? Matrix::transpose($result) : $result;
        }

        $result = self::countValuesCaseInsensitive($flattenedLookupVector);

        if ($exactlyOnce === true) {
            $result = self::exactlyOnceFilter($result);
        }

        if (count($result) === 0) {
            return ExcelError::CALC();
        }

        $result = array_keys($result);

        return $result;
    }

    private static function countValuesCaseInsensitive(array $caseSensitiveLookupValues): array
    {
        $caseInsensitiveCounts = array_count_values(
            array_map(
                fn (string $value): string => StringHelper::strToUpper($value),
                $caseSensitiveLookupValues
            )
        );

        $caseSensitiveCounts = [];
        foreach ($caseInsensitiveCounts as $caseInsensitiveKey => $count) {
            if (is_numeric($caseInsensitiveKey)) {
                $caseSensitiveCounts[$caseInsensitiveKey] = $count;
            } else {
                foreach ($caseSensitiveLookupValues as $caseSensitiveValue) {
                    if ($caseInsensitiveKey === StringHelper::strToUpper($caseSensitiveValue)) {
                        $caseSensitiveCounts[$caseSensitiveValue] = $count;

                        break;
                    }
                }
            }
        }

        return $caseSensitiveCounts;
    }

    private static function exactlyOnceFilter(array $values): array
    {
        return array_filter(
            $values,
            fn ($value): bool => $value === 1
        );
    }
}
