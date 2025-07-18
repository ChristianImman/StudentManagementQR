<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Combinations;

class Binomial
{
    use ArrayEnabled;

    
    public static function distribution(mixed $value, mixed $trials, mixed $probability, mixed $cumulative)
    {
        if (is_array($value) || is_array($trials) || is_array($probability) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $trials, $probability, $cumulative);
        }

        try {
            $value = DistributionValidations::validateInt($value);
            $trials = DistributionValidations::validateInt($trials);
            $probability = DistributionValidations::validateProbability($probability);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value < 0) || ($value > $trials)) {
            return ExcelError::NAN();
        }

        if ($cumulative) {
            return self::calculateCumulativeBinomial($value, $trials, $probability);
        }
        
        $comb = Combinations::withoutRepetition($trials, $value);

        return $comb * $probability ** $value
            * (1 - $probability) ** ($trials - $value);
    }

    
    public static function range(mixed $trials, mixed $probability, mixed $successes, mixed $limit = null): array|string|float|int
    {
        if (is_array($trials) || is_array($probability) || is_array($successes) || is_array($limit)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $trials, $probability, $successes, $limit);
        }

        $limit = $limit ?? $successes;

        try {
            $trials = DistributionValidations::validateInt($trials);
            $probability = DistributionValidations::validateProbability($probability);
            $successes = DistributionValidations::validateInt($successes);
            $limit = DistributionValidations::validateInt($limit);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($successes < 0) || ($successes > $trials)) {
            return ExcelError::NAN();
        }
        if (($limit < 0) || ($limit > $trials) || $limit < $successes) {
            return ExcelError::NAN();
        }

        $summer = 0;
        for ($i = $successes; $i <= $limit; ++$i) {
            
            $comb = Combinations::withoutRepetition($trials, $i);
            $summer += $comb * $probability ** $i
                * (1 - $probability) ** ($trials - $i);
        }

        return $summer;
    }

    
    public static function negative(mixed $failures, mixed $successes, mixed $probability): array|string|float
    {
        if (is_array($failures) || is_array($successes) || is_array($probability)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $failures, $successes, $probability);
        }

        try {
            $failures = DistributionValidations::validateInt($failures);
            $successes = DistributionValidations::validateInt($successes);
            $probability = DistributionValidations::validateProbability($probability);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($failures < 0) || ($successes < 1)) {
            return ExcelError::NAN();
        }
        if (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_GNUMERIC) {
            if (($failures + $successes - 1) <= 0) {
                return ExcelError::NAN();
            }
        }
        
        $comb = Combinations::withoutRepetition($failures + $successes - 1, $successes - 1);

        return $comb
            * ($probability ** $successes) * ((1 - $probability) ** $failures);
    }

    
    public static function inverse(mixed $trials, mixed $probability, mixed $alpha): array|string|int
    {
        if (is_array($trials) || is_array($probability) || is_array($alpha)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $trials, $probability, $alpha);
        }

        try {
            $trials = DistributionValidations::validateInt($trials);
            $probability = DistributionValidations::validateProbability($probability);
            $alpha = DistributionValidations::validateFloat($alpha);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($trials < 0) {
            return ExcelError::NAN();
        } elseif (($alpha < 0.0) || ($alpha > 1.0)) {
            return ExcelError::NAN();
        }

        $successes = 0;
        while ($successes <= $trials) {
            $result = self::calculateCumulativeBinomial($successes, $trials, $probability);
            if ($result >= $alpha) {
                break;
            }
            ++$successes;
        }

        return $successes;
    }

    private static function calculateCumulativeBinomial(int $value, int $trials, float $probability): float|int
    {
        $summer = 0;
        for ($i = 0; $i <= $value; ++$i) {
            
            $comb = Combinations::withoutRepetition($trials, $i);
            $summer += $comb * $probability ** $i
                * (1 - $probability) ** ($trials - $i);
        }

        return $summer;
    }
}
