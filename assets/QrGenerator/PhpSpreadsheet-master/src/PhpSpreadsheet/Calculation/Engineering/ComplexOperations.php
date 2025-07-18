<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use Complex\Complex as ComplexObject;
use Complex\Exception as ComplexException;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ComplexOperations
{
    use ArrayEnabled;

    
    public static function IMDIV(array|string $complexDividend, array|string $complexDivisor): array|string
    {
        if (is_array($complexDividend) || is_array($complexDivisor)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $complexDividend, $complexDivisor);
        }

        try {
            return (string) (new ComplexObject($complexDividend))->divideby(new ComplexObject($complexDivisor));
        } catch (ComplexException) {
            return ExcelError::NAN();
        }
    }

    
    public static function IMSUB(array|string $complexNumber1, array|string $complexNumber2): array|string
    {
        if (is_array($complexNumber1) || is_array($complexNumber2)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $complexNumber1, $complexNumber2);
        }

        try {
            return (string) (new ComplexObject($complexNumber1))->subtract(new ComplexObject($complexNumber2));
        } catch (ComplexException) {
            return ExcelError::NAN();
        }
    }

    
    public static function IMSUM(...$complexNumbers): string
    {
        
        $returnValue = new ComplexObject(0.0);
        $aArgs = Functions::flattenArray($complexNumbers);

        try {
            
            foreach ($aArgs as $complex) {
                $returnValue = $returnValue->add(new ComplexObject($complex));
            }
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $returnValue;
    }

    
    public static function IMPRODUCT(...$complexNumbers): string
    {
        
        $returnValue = new ComplexObject(1.0);
        $aArgs = Functions::flattenArray($complexNumbers);

        try {
            
            foreach ($aArgs as $complex) {
                $returnValue = $returnValue->multiply(new ComplexObject($complex));
            }
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $returnValue;
    }
}
