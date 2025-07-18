<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Gamma extends GammaBase
{
    use ArrayEnabled;

    
    public static function gamma(mixed $value): array|string|float
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ((((int) $value) == ((float) $value)) && $value <= 0.0) {
            return ExcelError::NAN();
        }

        return self::gammaValue($value);
    }

    
    public static function distribution(mixed $value, mixed $a, mixed $b, mixed $cumulative)
    {
        if (is_array($value) || is_array($a) || is_array($b) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $a, $b, $cumulative);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $a = DistributionValidations::validateFloat($a);
            $b = DistributionValidations::validateFloat($b);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value < 0) || ($a <= 0) || ($b <= 0)) {
            return ExcelError::NAN();
        }

        return self::calculateDistribution($value, $a, $b, $cumulative);
    }

    
    public static function inverse(mixed $probability, mixed $alpha, mixed $beta)
    {
        if (is_array($probability) || is_array($alpha) || is_array($beta)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $probability, $alpha, $beta);
        }

        try {
            $probability = DistributionValidations::validateProbability($probability);
            $alpha = DistributionValidations::validateFloat($alpha);
            $beta = DistributionValidations::validateFloat($beta);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($alpha <= 0.0) || ($beta <= 0.0)) {
            return ExcelError::NAN();
        }

        return self::calculateInverse($probability, $alpha, $beta);
    }

    
    public static function ln(mixed $value): array|string|float
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($value <= 0) {
            return ExcelError::NAN();
        }

        return log(self::gammaValue($value));
    }
}
