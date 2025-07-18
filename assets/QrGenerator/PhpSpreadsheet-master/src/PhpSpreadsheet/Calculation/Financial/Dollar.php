<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;

class Dollar
{
    use ArrayEnabled;

    
    public static function format(mixed $number, mixed $precision = 2)
    {
        return Format::DOLLAR($number, $precision);
    }

    
    public static function decimal(mixed $fractionalDollar = null, mixed $fraction = 0): array|string|float
    {
        if (is_array($fractionalDollar) || is_array($fraction)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $fractionalDollar, $fraction);
        }

        try {
            $fractionalDollar = FinancialValidations::validateFloat(
                Functions::flattenSingleValue($fractionalDollar) ?? 0.0
            );
            $fraction = FinancialValidations::validateInt(Functions::flattenSingleValue($fraction));
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($fraction < 0) {
            return ExcelError::NAN();
        }
        if ($fraction == 0) {
            return ExcelError::DIV0();
        }

        $dollars = ($fractionalDollar < 0) ? ceil($fractionalDollar) : floor($fractionalDollar);
        $cents = fmod($fractionalDollar, 1.0);
        $cents /= $fraction;
        $cents *= 10 ** ceil(log10($fraction));

        return $dollars + $cents;
    }

    
    public static function fractional(mixed $decimalDollar = null, mixed $fraction = 0): array|string|float
    {
        if (is_array($decimalDollar) || is_array($fraction)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $decimalDollar, $fraction);
        }

        try {
            $decimalDollar = FinancialValidations::validateFloat(
                Functions::flattenSingleValue($decimalDollar) ?? 0.0
            );
            $fraction = FinancialValidations::validateInt(Functions::flattenSingleValue($fraction));
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($fraction < 0) {
            return ExcelError::NAN();
        }
        if ($fraction == 0) {
            return ExcelError::DIV0();
        }

        $dollars = ($decimalDollar < 0.0) ? ceil($decimalDollar) : floor($decimalDollar);
        $cents = fmod($decimalDollar, 1);
        $cents *= $fraction;
        $cents *= 10 ** (-ceil(log10($fraction)));

        return $dollars + $cents;
    }
}
