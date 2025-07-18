<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Erf
{
    use ArrayEnabled;

    private const TWO_SQRT_PI = 1.128379167095512574;

    
    public static function ERF(mixed $lower, mixed $upper = null): array|float|string
    {
        if (is_array($lower) || is_array($upper)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $lower, $upper);
        }

        if (is_numeric($lower)) {
            if ($upper === null) {
                return self::erfValue($lower);
            }
            if (is_numeric($upper)) {
                return self::erfValue($upper) - self::erfValue($lower);
            }
        }

        return ExcelError::VALUE();
    }

    
    public static function ERFPRECISE(mixed $limit)
    {
        if (is_array($limit)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $limit);
        }

        return self::ERF($limit);
    }

    private static function makeFloat(mixed $value): float
    {
        return is_numeric($value) ? ((float) $value) : 0.0;
    }

    
    public static function erfValue(float|int|string $value): float
    {
        $value = (float) $value;
        if (abs($value) > 2.2) {
            return 1 - self::makeFloat(ErfC::ERFC($value));
        }
        $sum = $term = $value;
        $xsqr = ($value * $value);
        $j = 1;
        do {
            $term *= $xsqr / $j;
            $sum -= $term / (2 * $j + 1);
            ++$j;
            $term *= $xsqr / $j;
            $sum += $term / (2 * $j + 1);
            ++$j;
            if ($sum == 0.0) {
                break;
            }
        } while (abs($term / $sum) > Functions::PRECISION);

        return self::TWO_SQRT_PI * $sum;
    }
}
