<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Lcm
{
    
    
    
    private static function factors(float $value): array
    {
        $startVal = floor(sqrt($value));

        $factorArray = [];
        for ($i = $startVal; $i > 1; --$i) {
            if (($value % $i) == 0) {
                $factorArray = array_merge($factorArray, self::factors($value / $i));
                $factorArray = array_merge($factorArray, self::factors($i));
                if ($i <= sqrt($value)) {
                    break;
                }
            }
        }
        if (!empty($factorArray)) {
            rsort($factorArray);

            return $factorArray;
        }

        return [(int) $value];
    }

    
    public static function evaluate(mixed ...$args): int|string
    {
        try {
            $arrayArgs = [];
            $anyZeros = 0;
            $anyNonNulls = 0;
            foreach (Functions::flattenArray($args) as $value1) {
                $anyNonNulls += (int) ($value1 !== null);
                $value = Helpers::validateNumericNullSubstitution($value1, 1);
                Helpers::validateNotNegative($value);
                $arrayArgs[] = (int) $value;
                $anyZeros += (int) !((bool) $value);
            }
            self::testNonNulls($anyNonNulls);
            if ($anyZeros) {
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $returnValue = 1;
        $allPoweredFactors = [];
        
        foreach ($arrayArgs as $value) {
            $myFactors = self::factors(floor($value));
            $myCountedFactors = array_count_values($myFactors);
            $myPoweredFactors = [];
            foreach ($myCountedFactors as $myCountedFactor => $myCountedPower) {
                $myPoweredFactors[$myCountedFactor] = $myCountedFactor ** $myCountedPower;
            }
            self::processPoweredFactors($allPoweredFactors, $myPoweredFactors);
        }
        foreach ($allPoweredFactors as $allPoweredFactor) {
            $returnValue *= (int) $allPoweredFactor;
        }

        return $returnValue;
    }

    private static function processPoweredFactors(array &$allPoweredFactors, array &$myPoweredFactors): void
    {
        foreach ($myPoweredFactors as $myPoweredValue => $myPoweredFactor) {
            if (isset($allPoweredFactors[$myPoweredValue])) {
                if ($allPoweredFactors[$myPoweredValue] < $myPoweredFactor) {
                    $allPoweredFactors[$myPoweredValue] = $myPoweredFactor;
                }
            } else {
                $allPoweredFactors[$myPoweredValue] = $myPoweredFactor;
            }
        }
    }

    private static function testNonNulls(int $anyNonNulls): void
    {
        if (!$anyNonNulls) {
            throw new Exception(ExcelError::VALUE());
        }
    }
}
