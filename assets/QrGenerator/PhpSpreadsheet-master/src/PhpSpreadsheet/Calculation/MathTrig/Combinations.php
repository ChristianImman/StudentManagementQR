<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Combinations
{
    use ArrayEnabled;

    
    public static function withoutRepetition(mixed $numObjs, mixed $numInSet): array|string|float
    {
        if (is_array($numObjs) || is_array($numInSet)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $numObjs, $numInSet);
        }

        try {
            $numObjs = Helpers::validateNumericNullSubstitution($numObjs, null);
            $numInSet = Helpers::validateNumericNullSubstitution($numInSet, null);
            Helpers::validateNotNegative($numInSet);
            Helpers::validateNotNegative($numObjs - $numInSet);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $quotient = Factorial::fact($numObjs);
        
        $divisor1 = Factorial::fact($numObjs - $numInSet);
        
        $divisor2 = Factorial::fact($numInSet);

        return round($quotient / ($divisor1 * $divisor2));
    }

    
    public static function withRepetition(mixed $numObjs, mixed $numInSet): array|int|string|float
    {
        if (is_array($numObjs) || is_array($numInSet)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $numObjs, $numInSet);
        }

        try {
            $numObjs = Helpers::validateNumericNullSubstitution($numObjs, null);
            $numInSet = Helpers::validateNumericNullSubstitution($numInSet, null);
            Helpers::validateNotNegative($numInSet);
            Helpers::validateNotNegative($numObjs);
            $numObjs = (int) $numObjs;
            $numInSet = (int) $numInSet;
            
            
            
            if ($numObjs === 0) {
                Helpers::validateNotNegative(-$numInSet);

                return 1;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $quotient = Factorial::fact($numObjs + $numInSet - 1);
        
        $divisor1 = Factorial::fact($numObjs - 1);
        
        $divisor2 = Factorial::fact($numInSet);

        return round($quotient / ($divisor1 * $divisor2));
    }
}
