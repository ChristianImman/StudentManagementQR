<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Engineering;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Normal
{
    use ArrayEnabled;

    public const SQRT2PI = 2.5066282746310005024157652848110452530069867406099;

    
    public static function distribution(mixed $value, mixed $mean, mixed $stdDev, mixed $cumulative): array|string|float
    {
        if (is_array($value) || is_array($mean) || is_array($stdDev) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $mean, $stdDev, $cumulative);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $mean = DistributionValidations::validateFloat($mean);
            $stdDev = DistributionValidations::validateFloat($stdDev);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($stdDev < 0) {
            return ExcelError::NAN();
        }

        if ($cumulative) {
            return 0.5 * (1 + Engineering\Erf::erfValue(($value - $mean) / ($stdDev * sqrt(2))));
        }

        return (1 / (self::SQRT2PI * $stdDev)) * exp(0 - (($value - $mean) ** 2 / (2 * ($stdDev * $stdDev))));
    }

    
    public static function inverse(mixed $probability, mixed $mean, mixed $stdDev): array|string|float
    {
        if (is_array($probability) || is_array($mean) || is_array($stdDev)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $probability, $mean, $stdDev);
        }

        try {
            $probability = DistributionValidations::validateProbability($probability);
            $mean = DistributionValidations::validateFloat($mean);
            $stdDev = DistributionValidations::validateFloat($stdDev);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($stdDev < 0) {
            return ExcelError::NAN();
        }

        return (self::inverseNcdf($probability) * $stdDev) + $mean;
    }

    
    private static function inverseNcdf(float $p): float
    {
        
        
        
        
        

        
        
        

        

        
        static $a = [
            1 => -3.969683028665376e+01,
            2 => 2.209460984245205e+02,
            3 => -2.759285104469687e+02,
            4 => 1.383577518672690e+02,
            5 => -3.066479806614716e+01,
            6 => 2.506628277459239e+00,
        ];

        static $b = [
            1 => -5.447609879822406e+01,
            2 => 1.615858368580409e+02,
            3 => -1.556989798598866e+02,
            4 => 6.680131188771972e+01,
            5 => -1.328068155288572e+01,
        ];

        static $c = [
            1 => -7.784894002430293e-03,
            2 => -3.223964580411365e-01,
            3 => -2.400758277161838e+00,
            4 => -2.549732539343734e+00,
            5 => 4.374664141464968e+00,
            6 => 2.938163982698783e+00,
        ];

        static $d = [
            1 => 7.784695709041462e-03,
            2 => 3.224671290700398e-01,
            3 => 2.445134137142996e+00,
            4 => 3.754408661907416e+00,
        ];

        
        $p_low = 0.02425; 
        $p_high = 1 - $p_low; 

        if (0 < $p && $p < $p_low) {
            
            $q = sqrt(-2 * log($p));

            return ((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) * $q + $c[6])
                / (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) * $q + 1);
        } elseif ($p_high < $p && $p < 1) {
            
            $q = sqrt(-2 * log(1 - $p));

            return -((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) * $q + $c[6])
                / (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) * $q + 1);
        }

        
        $q = $p - 0.5;
        $r = $q * $q;

        return ((((($a[1] * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $r + $a[6]) * $q
                / ((((($b[1] * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + $b[5]) * $r + 1);
    }
}
