<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Weibull
{
    use ArrayEnabled;

    
    public static function distribution(mixed $value, mixed $alpha, mixed $beta, mixed $cumulative): array|string|float
    {
        if (is_array($value) || is_array($alpha) || is_array($beta) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $alpha, $beta, $cumulative);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $alpha = DistributionValidations::validateFloat($alpha);
            $beta = DistributionValidations::validateFloat($beta);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value < 0) || ($alpha <= 0) || ($beta <= 0)) {
            return ExcelError::NAN();
        }

        if ($cumulative) {
            return 1 - exp(0 - ($value / $beta) ** $alpha);
        }

        return ($alpha / $beta ** $alpha) * $value ** ($alpha - 1) * exp(0 - ($value / $beta) ** $alpha);
    }
}
