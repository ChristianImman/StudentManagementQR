<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Ceiling
{
    use ArrayEnabled;

    
    public static function ceiling($number, $significance = null)
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

    
    public static function math(mixed $number, mixed $significance = null, $mode = 0): array|string|float
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

        if (empty($significance * $number)) {
            return 0.0;
        }
        if (self::ceilingMathTest((float) $significance, (float) $number, (int) $mode)) {
            return floor($number / $significance) * $significance;
        }

        return ceil($number / $significance) * $significance;
    }

    
    public static function precise(mixed $number, $significance = 1): array|string|float
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

        if (!$significance) {
            return 0.0;
        }
        $result = $number / abs($significance);

        return ceil($result) * $significance * (($significance < 0) ? -1 : 1);
    }

    
    private static function ceilingMathTest(float $significance, float $number, int $mode): bool
    {
        return ($significance < 0) || ($number < 0 && !empty($mode));
    }

    
    private static function argumentsOk(float $number, float $significance): float|string
    {
        if (empty($number * $significance)) {
            return 0.0;
        }
        if (Helpers::returnSign($number) == Helpers::returnSign($significance)) {
            return ceil($number / $significance) * $significance;
        }

        return ExcelError::NAN();
    }

    private static function floorCheck1Arg(): void
    {
        $compatibility = Functions::getCompatibilityMode();
        if ($compatibility === Functions::COMPATIBILITY_EXCEL) {
            throw new Exception('Excel requires 2 arguments for CEILING');
        }
    }
}
