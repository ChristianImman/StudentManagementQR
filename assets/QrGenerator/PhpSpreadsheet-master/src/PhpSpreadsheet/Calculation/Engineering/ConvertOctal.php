<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ConvertOctal extends ConvertBase
{
    
    public static function toBinary($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateOctal($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return ConvertDecimal::toBinary(self::toDecimal($value), $places);
    }

    
    public static function toDecimal($value)
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateOctal($value);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $binX = '';
        foreach (mb_str_split($value, 1, 'UTF-8') as $char) {
            $binX .= str_pad(decbin((int) $char), 3, '0', STR_PAD_LEFT);
        }
        if (strlen($binX) == 30 && $binX[0] == '1') {
            for ($i = 0; $i < 30; ++$i) {
                $binX[$i] = ($binX[$i] == '1' ? '0' : '1');
            }

            return (bindec($binX) + 1) * -1;
        }

        return bindec($binX);
    }

    
    public static function toHex($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateOctal($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $hexVal = strtoupper(dechex((int) self::toDecimal($value)));
        $hexVal = (PHP_INT_SIZE === 4 && strlen($value) === 10 && $value[0] >= '4') ? "FF{$hexVal}" : $hexVal;

        return self::nbrConversionFormat($hexVal, $places);
    }

    protected static function validateOctal(string $value): string
    {
        $numDigits = (int) preg_match_all('/[01234567]/', $value);
        if (strlen($value) > $numDigits || $numDigits > 10) {
            throw new Exception(ExcelError::NAN());
        }

        return $value;
    }
}
