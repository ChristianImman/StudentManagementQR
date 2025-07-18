<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ConvertHex extends ConvertBase
{
    
    public static function toBinary($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateHex($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $dec = self::toDecimal($value);

        return ConvertDecimal::toBinary($dec, $places);
    }

    
    public static function toDecimal($value)
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateHex($value);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (strlen($value) > 10) {
            return ExcelError::NAN();
        }

        $binX = '';
        foreach (mb_str_split($value, 1, 'UTF-8') as $char) {
            $binX .= str_pad(base_convert($char, 16, 2), 4, '0', STR_PAD_LEFT);
        }
        if (strlen($binX) == 40 && $binX[0] == '1') {
            for ($i = 0; $i < 40; ++$i) {
                $binX[$i] = ($binX[$i] == '1' ? '0' : '1');
            }

            return (bindec($binX) + 1) * -1;
        }

        return bindec($binX);
    }

    
    public static function toOctal($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateHex($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $decimal = self::toDecimal($value);

        return ConvertDecimal::toOctal($decimal, $places);
    }

    protected static function validateHex(string $value): string
    {
        if (strlen($value) > preg_match_all('/[0123456789ABCDEF]/', $value)) {
            throw new Exception(ExcelError::NAN());
        }

        return $value;
    }
}
