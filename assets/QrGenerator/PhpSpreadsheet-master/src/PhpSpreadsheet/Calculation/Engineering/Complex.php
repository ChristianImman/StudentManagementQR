<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use Complex\Complex as ComplexObject;
use Complex\Exception as ComplexException;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Complex
{
    use ArrayEnabled;

    
    public static function COMPLEX(mixed $realNumber = 0.0, mixed $imaginary = 0.0, mixed $suffix = 'i'): array|string
    {
        if (is_array($realNumber) || is_array($imaginary) || is_array($suffix)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $realNumber, $imaginary, $suffix);
        }

        $realNumber = $realNumber ?? 0.0;
        $imaginary = $imaginary ?? 0.0;
        $suffix = $suffix ?? 'i';

        try {
            $realNumber = EngineeringValidations::validateFloat($realNumber);
            $imaginary = EngineeringValidations::validateFloat($imaginary);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($suffix === 'i') || ($suffix === 'j') || ($suffix === '')) {
            $complex = new ComplexObject($realNumber, $imaginary, $suffix);

            return (string) $complex;
        }

        return ExcelError::VALUE();
    }

    
    public static function IMAGINARY($complexNumber): array|string|float
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return $complex->getImaginary();
    }

    
    public static function IMREAL($complexNumber): array|string|float
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return $complex->getReal();
    }
}
