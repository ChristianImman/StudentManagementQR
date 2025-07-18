<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

class Poisson
{
    use ArrayEnabled;

    
    public static function distribution(mixed $value, mixed $mean, mixed $cumulative): array|string|float
    {
        if (is_array($value) || is_array($mean) || is_array($cumulative)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $mean, $cumulative);
        }

        try {
            $value = DistributionValidations::validateFloat($value);
            $mean = DistributionValidations::validateFloat($mean);
            $cumulative = DistributionValidations::validateBool($cumulative);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($value < 0) || ($mean < 0)) {
            return ExcelError::NAN();
        }

        if ($cumulative) {
            $summer = 0;
            $floor = floor($value);
            for ($i = 0; $i <= $floor; ++$i) {
                
                $fact = MathTrig\Factorial::fact($i);
                $summer += $mean ** $i / $fact;
            }

            return exp(0 - $mean) * $summer;
        }
        
        $fact = MathTrig\Factorial::fact($value);

        return (exp(0 - $mean) * $mean ** $value) / $fact;
    }
}
