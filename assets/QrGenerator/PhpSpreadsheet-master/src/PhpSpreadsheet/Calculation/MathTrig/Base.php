<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Base
{
    use ArrayEnabled;

    
    public static function evaluate(mixed $number, mixed $radix, mixed $minLength = null): array|string
    {
        if (is_array($number) || is_array($radix) || is_array($minLength)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $radix, $minLength);
        }

        try {
            $number = (float) floor(Helpers::validateNumericNullBool($number));
            $radix = (int) Helpers::validateNumericNullBool($radix);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::calculate($number, $radix, $minLength);
    }

    private static function calculate(float $number, int $radix, mixed $minLength): string
    {
        if ($minLength === null || is_numeric($minLength)) {
            if ($number < 0 || $number >= 2 ** 53 || $radix < 2 || $radix > 36) {
                return ExcelError::NAN(); 
            }

            $outcome = strtoupper((string) base_convert("$number", 10, $radix));
            if ($minLength !== null) {
                $outcome = str_pad($outcome, (int) $minLength, '0', STR_PAD_LEFT); 
            }

            return $outcome;
        }

        return ExcelError::VALUE();
    }
}
