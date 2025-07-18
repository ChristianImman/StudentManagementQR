<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class BitWise
{
    use ArrayEnabled;

    const SPLIT_DIVISOR = 2 ** 24;

    
    private static function splitNumber(float|int $number): array
    {
        return [(int) floor($number / self::SPLIT_DIVISOR), (int) fmod($number, self::SPLIT_DIVISOR)];
    }

    
    public static function BITAND(null|array|bool|float|int|string $number1, null|array|bool|float|int|string $number2): array|string|int|float
    {
        if (is_array($number1) || is_array($number2)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number1, $number2);
        }

        try {
            $number1 = self::validateBitwiseArgument($number1);
            $number2 = self::validateBitwiseArgument($number2);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        $split1 = self::splitNumber($number1);
        $split2 = self::splitNumber($number2);

        return self::SPLIT_DIVISOR * ($split1[0] & $split2[0]) + ($split1[1] & $split2[1]);
    }

    
    public static function BITOR(null|array|bool|float|int|string $number1, null|array|bool|float|int|string $number2): array|string|int|float
    {
        if (is_array($number1) || is_array($number2)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number1, $number2);
        }

        try {
            $number1 = self::validateBitwiseArgument($number1);
            $number2 = self::validateBitwiseArgument($number2);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $split1 = self::splitNumber($number1);
        $split2 = self::splitNumber($number2);

        return self::SPLIT_DIVISOR * ($split1[0] | $split2[0]) + ($split1[1] | $split2[1]);
    }

    
    public static function BITXOR(null|array|bool|float|int|string $number1, null|array|bool|float|int|string $number2): array|string|int|float
    {
        if (is_array($number1) || is_array($number2)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number1, $number2);
        }

        try {
            $number1 = self::validateBitwiseArgument($number1);
            $number2 = self::validateBitwiseArgument($number2);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $split1 = self::splitNumber($number1);
        $split2 = self::splitNumber($number2);

        return self::SPLIT_DIVISOR * ($split1[0] ^ $split2[0]) + ($split1[1] ^ $split2[1]);
    }

    
    public static function BITLSHIFT(null|array|bool|float|int|string $number, null|array|bool|float|int|string $shiftAmount): array|string|float
    {
        if (is_array($number) || is_array($shiftAmount)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $shiftAmount);
        }

        try {
            $number = self::validateBitwiseArgument($number);
            $shiftAmount = self::validateShiftAmount($shiftAmount);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $result = floor($number * (2 ** $shiftAmount));
        if ($result > 2 ** 48 - 1) {
            return ExcelError::NAN();
        }

        return $result;
    }

    
    public static function BITRSHIFT(null|array|bool|float|int|string $number, null|array|bool|float|int|string $shiftAmount): array|string|float
    {
        if (is_array($number) || is_array($shiftAmount)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $shiftAmount);
        }

        try {
            $number = self::validateBitwiseArgument($number);
            $shiftAmount = self::validateShiftAmount($shiftAmount);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $result = floor($number / (2 ** $shiftAmount));
        if ($result > 2 ** 48 - 1) { 
            return ExcelError::NAN();
        }

        return $result;
    }

    
    private static function validateBitwiseArgument(mixed $value): float
    {
        $value = self::nullFalseTrueToNumber($value);

        if (is_numeric($value)) {
            $value = (float) $value;
            if ($value == floor($value)) {
                if (($value > 2 ** 48 - 1) || ($value < 0)) {
                    throw new Exception(ExcelError::NAN());
                }

                return floor($value);
            }

            throw new Exception(ExcelError::NAN());
        }

        throw new Exception(ExcelError::VALUE());
    }

    
    private static function validateShiftAmount(mixed $value): int
    {
        $value = self::nullFalseTrueToNumber($value);

        if (is_numeric($value)) {
            if (abs($value + 0) > 53) {
                throw new Exception(ExcelError::NAN());
            }

            return (int) $value;
        }

        throw new Exception(ExcelError::VALUE());
    }

    
    private static function nullFalseTrueToNumber(mixed &$number): mixed
    {
        if ($number === null) {
            $number = 0;
        } elseif (is_bool($number)) {
            $number = (int) $number;
        }

        return $number;
    }
}
