<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ErfC
{
    use ArrayEnabled;

    
    public static function ERFC(mixed $value)
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if (is_numeric($value)) {
            return self::erfcValue($value);
        }

        return ExcelError::VALUE();
    }

    private const ONE_SQRT_PI = 0.564189583547756287;

    
    private static function erfcValue(float|int|string $value): float|int
    {
        $value = (float) $value;
        if (abs($value) < 2.2) {
            return 1 - Erf::erfValue($value);
        }
        if ($value < 0) {
            return 2 - self::erfcValue(-$value);
        }
        $a = $n = 1;
        $b = $c = $value;
        $d = ($value * $value) + 0.5;
        $q2 = $b / $d;
        do {
            $t = $a * $n + $b * $value;
            $a = $b;
            $b = $t;
            $t = $c * $n + $d * $value;
            $c = $d;
            $d = $t;
            $n += 0.5;
            $q1 = $q2;
            $q2 = $b / $d;
        } while ((abs($q1 - $q2) / $q2) > Functions::PRECISION);

        return self::ONE_SQRT_PI * exp(-$value * $value) * $q2;
    }
}
