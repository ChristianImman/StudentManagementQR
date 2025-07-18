<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use Complex\Complex as ComplexObject;
use Complex\Exception as ComplexException;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ComplexFunctions
{
    use ArrayEnabled;

    
    public static function IMABS(array|string $complexNumber): array|float|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return $complex->abs();
    }

    
    public static function IMARGUMENT(array|string $complexNumber): array|float|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return ExcelError::DIV0();
        }

        return $complex->argument();
    }

    
    public static function IMCONJUGATE(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->conjugate();
    }

    
    public static function IMCOS(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->cos();
    }

    
    public static function IMCOSH(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->cosh();
    }

    
    public static function IMCOT(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->cot();
    }

    
    public static function IMCSC(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->csc();
    }

    
    public static function IMCSCH(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->csch();
    }

    
    public static function IMSIN(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->sin();
    }

    
    public static function IMSINH(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->sinh();
    }

    
    public static function IMSEC(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->sec();
    }

    
    public static function IMSECH(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->sech();
    }

    
    public static function IMTAN(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->tan();
    }

    
    public static function IMSQRT(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        $theta = self::IMARGUMENT($complexNumber);
        if ($theta === ExcelError::DIV0()) {
            return '0';
        }

        return (string) $complex->sqrt();
    }

    
    public static function IMLN(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return ExcelError::NAN();
        }

        return (string) $complex->ln();
    }

    
    public static function IMLOG10(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return ExcelError::NAN();
        }

        return (string) $complex->log10();
    }

    
    public static function IMLOG2(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        if ($complex->getReal() == 0.0 && $complex->getImaginary() == 0.0) {
            return ExcelError::NAN();
        }

        return (string) $complex->log2();
    }

    
    public static function IMEXP(array|string $complexNumber): array|string
    {
        if (is_array($complexNumber)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $complexNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        return (string) $complex->exp();
    }

    
    public static function IMPOWER(array|string $complexNumber, array|float|int|string $realNumber): array|string
    {
        if (is_array($complexNumber) || is_array($realNumber)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $complexNumber, $realNumber);
        }

        try {
            $complex = new ComplexObject($complexNumber);
        } catch (ComplexException) {
            return ExcelError::NAN();
        }

        if (!is_numeric($realNumber)) {
            return ExcelError::VALUE();
        }

        return (string) $complex->pow((float) $realNumber);
    }
}
