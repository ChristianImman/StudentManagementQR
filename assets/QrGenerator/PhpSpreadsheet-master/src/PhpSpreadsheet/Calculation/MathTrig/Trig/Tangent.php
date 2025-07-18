<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Trig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Helpers;

class Tangent
{
    use ArrayEnabled;

    
    public static function tan(mixed $angle)
    {
        if (is_array($angle)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $angle);
        }

        try {
            $angle = Helpers::validateNumericNullBool($angle);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::verySmallDenominator(sin($angle), cos($angle));
    }

    
    public static function tanh(mixed $angle): array|string|float
    {
        if (is_array($angle)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $angle);
        }

        try {
            $angle = Helpers::validateNumericNullBool($angle);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return tanh($angle);
    }

    
    public static function atan($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::numberOrNan(atan($number));
    }

    
    public static function atanh($number)
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::numberOrNan(atanh($number));
    }

    
    public static function atan2(mixed $xCoordinate, mixed $yCoordinate): array|string|float
    {
        if (is_array($xCoordinate) || is_array($yCoordinate)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $xCoordinate, $yCoordinate);
        }

        try {
            $xCoordinate = Helpers::validateNumericNullBool($xCoordinate);
            $yCoordinate = Helpers::validateNumericNullBool($yCoordinate);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($xCoordinate == 0) && ($yCoordinate == 0)) {
            return ExcelError::DIV0();
        }

        return atan2($yCoordinate, $xCoordinate);
    }
}
