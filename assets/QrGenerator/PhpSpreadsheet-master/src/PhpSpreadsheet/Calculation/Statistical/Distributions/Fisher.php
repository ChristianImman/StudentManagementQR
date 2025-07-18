<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Fisher
{
    use ArrayEnabled;

    
    public static function distribution(mixed $value): array|string|float
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value <= -1) || ($value >= 1)) {
            return ExcelError::NAN();
        }

        return 0.5 * log((1 + $value) / (1 - $value));
    }

    
    public static function inverse(mixed $probability): array|string|float
    {
        if (is_array($probability)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $probability);
        }

        try {
            $probability = DistributionValidations::validateFloat($probability);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return (exp(2 * $probability) - 1) / (exp(2 * $probability) + 1);
    }
}
