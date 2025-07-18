<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\StandardDeviations;

class StandardNormal
{
    use ArrayEnabled;

    
    public static function cumulative(mixed $value)
    {
        return Normal::distribution($value, 0, 1, true);
    }

    
    public static function distribution(mixed $value, mixed $cumulative)
    {
        return Normal::distribution($value, 0, 1, $cumulative);
    }

    
    public static function inverse(mixed $value)
    {
        return Normal::inverse($value, 0, 1);
    }

    
    public static function gauss(mixed $value): array|string|float
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if (!is_numeric($value)) {
            return ExcelError::VALUE();
        }
        
        $dist = self::distribution($value, true);

        return $dist - 0.5;
    }

    
    public static function zTest(mixed $dataSet, mixed $m0, mixed $sigma = null)
    {
        if (is_array($m0) || is_array($sigma)) {
            return self::evaluateArrayArgumentsSubsetFrom([self::class, __FUNCTION__], 1, $dataSet, $m0, $sigma);
        }

        $dataSet = Functions::flattenArrayIndexed($dataSet);

        if (!is_numeric($m0) || ($sigma !== null && !is_numeric($sigma))) {
            return ExcelError::VALUE();
        }

        if ($sigma === null) {
            
            $sigma = StandardDeviations::STDEV($dataSet);
        }
        $n = count($dataSet);

        $sub1 = Averages::average($dataSet);

        if (!is_numeric($sub1)) {
            return $sub1;
        }

        $temp = self::cumulative(($sub1 - $m0) / ($sigma / sqrt($n)));

        return 1 - (is_numeric($temp) ? $temp : 0);
    }
}
