<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

abstract class LookupBase
{
    protected static function validateLookupArray(mixed $lookup_array): void
    {
        if (!is_array($lookup_array)) {
            throw new Exception(ExcelError::REF());
        }
    }

    
    protected static function validateIndexLookup(array $lookup_array, $index_number): int
    {
        
        
        
        
        
        
        if (!is_numeric($index_number)) {
            throw new Exception(ExcelError::throwError($index_number));
        }
        if ($index_number < 1) {
            throw new Exception(ExcelError::VALUE());
        }

        
        if (empty($lookup_array)) {
            throw new Exception(ExcelError::REF());
        }

        return (int) $index_number;
    }

    protected static function checkMatch(
        bool $bothNumeric,
        bool $bothNotNumeric,
        bool $notExactMatch,
        int $rowKey,
        string $cellDataLower,
        string $lookupLower,
        ?int $rowNumber
    ): ?int {
        
        if ($bothNumeric || $bothNotNumeric) {
            
            
            
            if ($notExactMatch) {
                $rowNumber = $rowKey;
            } elseif (($cellDataLower == $lookupLower) && (($rowNumber === null) || ($rowKey < $rowNumber))) {
                $rowNumber = $rowKey;
            }
        }

        return $rowNumber;
    }
}
