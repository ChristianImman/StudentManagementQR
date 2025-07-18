<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Gcd
{
    
    private static function evaluateGCD(float|int $a, float|int $b): float|int
    {
        return $b ? self::evaluateGCD($b, $a % $b) : $a;
    }

    
    public static function evaluate(mixed ...$args)
    {
        try {
            $arrayArgs = [];
            foreach (Functions::flattenArray($args) as $value1) {
                if ($value1 !== null) {
                    $value = Helpers::validateNumericNullSubstitution($value1, 1);
                    Helpers::validateNotNegative($value);
                    $arrayArgs[] = (int) $value;
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (count($arrayArgs) <= 0) {
            return ExcelError::VALUE();
        }
        $gcd = (int) array_pop($arrayArgs);
        do {
            $gcd = self::evaluateGCD($gcd, (int) array_pop($arrayArgs));
        } while (!empty($arrayArgs));

        return $gcd;
    }
}
