<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;

class Trunc
{
    use ArrayEnabled;

    
    public static function evaluate(array|float|string|null $value = 0, array|float|int|string $digits = 0): array|float|string
    {
        if (is_array($value) || is_array($digits)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $digits);
        }

        return Round::down($value, $digits);
    }
}
