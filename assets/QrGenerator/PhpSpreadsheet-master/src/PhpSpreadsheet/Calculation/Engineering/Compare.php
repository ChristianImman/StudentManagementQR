<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Compare
{
    use ArrayEnabled;

    
    public static function DELTA(array|float|bool|string|int $a, array|float|bool|string|int $b = 0.0): array|string|int
    {
        if (is_array($a) || is_array($b)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $a, $b);
        }

        try {
            $a = EngineeringValidations::validateFloat($a);
            $b = EngineeringValidations::validateFloat($b);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return (int) (abs($a - $b) < 1.0e-15);
    }

    
    public static function GESTEP(array|float|bool|string|int $number, $step = 0.0): array|string|int
    {
        if (is_array($number) || is_array($step)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $step);
        }

        try {
            $number = EngineeringValidations::validateFloat($number);
            $step = EngineeringValidations::validateFloat($step ?? 0.0);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return (int) ($number >= $step);
    }
}
