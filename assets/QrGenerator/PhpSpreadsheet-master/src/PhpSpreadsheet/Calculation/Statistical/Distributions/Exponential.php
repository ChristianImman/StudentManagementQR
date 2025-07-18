<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Exponential
{
    use ArrayEnabled;

    
    public static function distribution(mixed $value, mixed $lambda, mixed $cumulative): array|string|float
    {
        if (is_array($value) || is_array($lambda) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $lambda, $cumulative);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $lambda = DistributionValidations::validateFloat($lambda);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value < 0) || ($lambda < 0)) {
            return ExcelError::NAN();
        }

        if ($cumulative === true) {
            return 1 - exp(0 - $value * $lambda);
        }

        return $lambda * exp(0 - $value * $lambda);
    }
}
