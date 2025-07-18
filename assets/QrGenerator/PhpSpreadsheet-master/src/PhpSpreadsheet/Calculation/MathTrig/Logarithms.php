<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Logarithms
{
    use ArrayEnabled;

    
    public static function withBase(mixed $number, mixed $base = 10): array|string|float
    {
        if (is_array($number) || is_array($base)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $base);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            Helpers::validatePositive($number);
            $base = Helpers::validateNumericNullBool($base);
            Helpers::validatePositive($base);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return log($number, $base);
    }

    
    public static function base10(mixed $number): array|string|float
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            Helpers::validatePositive($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return log10($number);
    }

    
    public static function natural(mixed $number): array|string|float
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            Helpers::validatePositive($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return log($number);
    }
}
