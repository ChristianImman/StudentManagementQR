<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class InterestRate
{
    
    public static function effective(mixed $nominalRate = 0, mixed $periodsPerYear = 0): string|float
    {
        $nominalRate = Functions::flattenSingleValue($nominalRate);
        $periodsPerYear = Functions::flattenSingleValue($periodsPerYear);

        try {
            $nominalRate = FinancialValidations::validateFloat($nominalRate);
            $periodsPerYear = FinancialValidations::validateInt($periodsPerYear);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($nominalRate <= 0 || $periodsPerYear < 1) {
            return ExcelError::NAN();
        }

        return ((1 + $nominalRate / $periodsPerYear) ** $periodsPerYear) - 1;
    }

    
    public static function nominal(mixed $effectiveRate = 0, mixed $periodsPerYear = 0): string|float
    {
        $effectiveRate = Functions::flattenSingleValue($effectiveRate);
        $periodsPerYear = Functions::flattenSingleValue($periodsPerYear);

        try {
            $effectiveRate = FinancialValidations::validateFloat($effectiveRate);
            $periodsPerYear = FinancialValidations::validateInt($periodsPerYear);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($effectiveRate <= 0 || $periodsPerYear < 1) {
            return ExcelError::NAN();
        }

        
        return $periodsPerYear * (($effectiveRate + 1) ** (1 / $periodsPerYear) - 1);
    }
}
