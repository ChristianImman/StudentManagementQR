<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;
use PhpOffice\PhpSpreadsheet\Shared\IntOrFloat;

class Permutations
{
    use ArrayEnabled;

    
    public static function PERMUT(mixed $numObjs, mixed $numInSet)
    {
        if (is_array($numObjs) || is_array($numInSet)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $numObjs, $numInSet);
        }

        try {
            $numObjs = StatisticalValidations::validateInt($numObjs);
            $numInSet = StatisticalValidations::validateInt($numInSet);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($numObjs < $numInSet) {
            return ExcelError::NAN();
        }
        
        $result1 = MathTrig\Factorial::fact($numObjs);
        if (is_string($result1)) {
            return $result1;
        }
        
        $result2 = MathTrig\Factorial::fact($numObjs - $numInSet);
        if (is_string($result2)) {
            return $result2;
        }
        $result = round($result1 / $result2);

        return IntOrFloat::evaluate($result);
    }

    
    public static function PERMUTATIONA(mixed $numObjs, mixed $numInSet)
    {
        if (is_array($numObjs) || is_array($numInSet)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $numObjs, $numInSet);
        }

        try {
            $numObjs = StatisticalValidations::validateInt($numObjs);
            $numInSet = StatisticalValidations::validateInt($numInSet);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($numObjs < 0 || $numInSet < 0) {
            return ExcelError::NAN();
        }

        $result = $numObjs ** $numInSet;

        return IntOrFloat::evaluate($result);
    }
}
