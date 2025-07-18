<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Floor
{
    use ArrayEnabled;

    private static function floorCheck1Arg(): void
    {
        $compatibility = Functions::getCompatibilityMode();
        if ($compatibility === Functions::COMPATIBILITY_EXCEL) {
            throw new Exception('Excel requires 2 arguments for FLOOR');
        }
    }

    
    public static function floor(mixed $number, mixed $significance = null)
    {
        if (is_array($number) || is_array($significance)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $significance);
        }

        if ($significance === null) {
            self::floorCheck1Arg();
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            $significance = Helpers::validateNumericNullSubstitution($significance, ($number < 0) ? -1 : 1);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::argumentsOk((float) $number, (float) $significance);
    }

    
    public static function math(mixed $number, mixed $significance = null, mixed $mode = 0)
    {
        if (is_array($number) || is_array($significance) || is_array($mode)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $significance, $mode);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            $significance = Helpers::validateNumericNullSubstitution($significance, ($number < 0) ? -1 : 1);
            $mode = Helpers::validateNumericNullSubstitution($mode, null);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::argsOk((float) $number, (float) $significance, (int) $mode);
    }

    
    public static function precise($number, $significance = 1)
    {
        if (is_array($number) || is_array($significance)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $significance);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            $significance = Helpers::validateNumericNullSubstitution($significance, null);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::argumentsOkPrecise((float) $number, (float) $significance);
    }

    
    private static function argumentsOkPrecise(float $number, float $significance): string|float
    {
        if ($significance == 0.0) {
            return ExcelError::DIV0();
        }
        if ($number == 0.0) {
            return 0.0;
        }

        return floor($number / abs($significance)) * abs($significance);
    }

    
    private static function argsOk(float $number, float $significance, int $mode): string|float
    {
        if (!$significance) {
            return ExcelError::DIV0();
        }
        if (!$number) {
            return 0.0;
        }
        if (self::floorMathTest($number, $significance, $mode)) {
            return ceil($number / $significance) * $significance;
        }

        return floor($number / $significance) * $significance;
    }

    
    private static function floorMathTest(float $number, float $significance, int $mode): bool
    {
        return Helpers::returnSign($significance) == -1 || (Helpers::returnSign($number) == -1 && !empty($mode));
    }

    
    private static function argumentsOk(float $number, float $significance): string|float
    {
        if ($significance == 0.0) {
            return ExcelError::DIV0();
        }
        if ($number == 0.0) {
            return 0.0;
        }
        if (Helpers::returnSign($significance) == 1) {
            return floor($number / $significance) * $significance;
        }
        if (Helpers::returnSign($number) == -1 && Helpers::returnSign($significance) == -1) {
            return floor($number / $significance) * $significance;
        }

        return ExcelError::NAN();
    }
}
