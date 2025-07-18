<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ConvertBinary extends ConvertBase
{
    
    public static function toDecimal($value)
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateBinary($value);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (strlen($value) == 10 && $value[0] === '1') {
            
            $value = substr($value, -9);

            return -(512 - bindec($value));
        }

        return bindec($value);
    }

    
    public static function toHex($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateBinary($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (strlen($value) == 10 && $value[0] === '1') {
            $high2 = substr($value, 0, 2);
            $low8 = substr($value, 2);
            $xarr = ['00' => '00000000', '01' => '00000001', '10' => 'FFFFFFFE', '11' => 'FFFFFFFF'];

            return $xarr[$high2] . strtoupper(substr('0' . dechex((int) bindec($low8)), -2));
        }
        $hexVal = (string) strtoupper(dechex((int) bindec($value)));

        return self::nbrConversionFormat($hexVal, $places);
    }

    
    public static function toOctal($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateBinary($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (strlen($value) == 10 && $value[0] === '1') { 
            return str_repeat('7', 6) . strtoupper(decoct((int) bindec("11$value")));
        }
        $octVal = (string) decoct((int) bindec($value));

        return self::nbrConversionFormat($octVal, $places);
    }

    protected static function validateBinary(string $value): string
    {
        if ((strlen($value) > preg_match_all('/[01]/', $value)) || (strlen($value) > 10)) {
            throw new Exception(ExcelError::NAN());
        }

        return $value;
    }
}
