<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class StudentT
{
    use ArrayEnabled;

    
    public static function distribution(mixed $value, mixed $degrees, mixed $tails)
    {
        if (is_array($value) || is_array($degrees) || is_array($tails)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $degrees, $tails);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $degrees = DistributionValidations::validateInt($degrees);
            $tails = DistributionValidations::validateInt($tails);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value < 0) || ($degrees < 1) || ($tails < 1) || ($tails > 2)) {
            return ExcelError::NAN();
        }

        return self::calculateDistribution($value, $degrees, $tails);
    }

    
    public static function inverse(mixed $probability, mixed $degrees)
    {
        if (is_array($probability) || is_array($degrees)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $probability, $degrees);
        }

        try {
            $probability = DistributionValidations::validateProbability($probability);
            $degrees = DistributionValidations::validateInt($degrees);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($degrees <= 0) {
            return ExcelError::NAN();
        }

        $callback = fn ($value) => self::distribution($value, $degrees, 2);

        $newtonRaphson = new NewtonRaphson($callback);

        return $newtonRaphson->execute($probability);
    }

    private static function calculateDistribution(float $value, int $degrees, int $tails): float
    {
        
        
        
        
        
        
        
        
        
        $tterm = $degrees;
        $ttheta = atan2($value, sqrt($tterm));
        $tc = cos($ttheta);
        $ts = sin($ttheta);

        if (($degrees % 2) === 1) {
            $ti = 3;
            $tterm = $tc;
        } else {
            $ti = 2;
            $tterm = 1;
        }

        $tsum = $tterm;
        while ($ti < $degrees) {
            $tterm *= $tc * $tc * ($ti - 1) / $ti;
            $tsum += $tterm;
            $ti += 2;
        }

        $tsum *= $ts;
        if (($degrees % 2) == 1) {
            $tsum = Functions::M_2DIVPI * ($tsum + $ttheta);
        }

        $tValue = 0.5 * (1 + $tsum);
        if ($tails == 1) {
            return 1 - abs($tValue);
        }

        return 1 - abs((1 - $tValue) - $tValue);
    }
}
