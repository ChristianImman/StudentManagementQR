<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class ConvertDecimal extends ConvertBase
{
    const LARGEST_OCTAL_IN_DECIMAL = 536870911;
    const SMALLEST_OCTAL_IN_DECIMAL = -536870912;
    const LARGEST_BINARY_IN_DECIMAL = 511;
    const SMALLEST_BINARY_IN_DECIMAL = -512;
    const LARGEST_HEX_IN_DECIMAL = 549755813887;
    const SMALLEST_HEX_IN_DECIMAL = -549755813888;

    
    public static function toBinary($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateDecimal($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $value = (int) floor((float) $value);
        if ($value > self::LARGEST_BINARY_IN_DECIMAL || $value < self::SMALLEST_BINARY_IN_DECIMAL) {
            return ExcelError::NAN();
        }

        $r = decbin($value);
        
        $r = substr($r, -10);

        return self::nbrConversionFormat($r, $places);
    }

    
    public static function toHex($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateDecimal($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $value = floor((float) $value);
        if ($value > self::LARGEST_HEX_IN_DECIMAL || $value < self::SMALLEST_HEX_IN_DECIMAL) {
            return ExcelError::NAN();
        }
        $r = strtoupper(dechex((int) $value));
        $r = self::hex32bit($value, $r);

        return self::nbrConversionFormat($r, $places);
    }

    public static function hex32bit(float $value, string $hexstr, bool $force = false): string
    {
        if (PHP_INT_SIZE === 4 || $force) {
            if ($value >= 2 ** 32) {
                $quotient = (int) ($value / (2 ** 32));

                return strtoupper(substr('0' . dechex($quotient), -2) . $hexstr);
            }
            if ($value < -(2 ** 32)) {
                $quotient = 256 - (int) ceil((-$value) / (2 ** 32));

                return strtoupper(substr('0' . dechex($quotient), -2) . substr("00000000$hexstr", -8));
            }
            if ($value < 0) {
                return "FF$hexstr";
            }
        }

        return $hexstr;
    }

    
    public static function toOctal($value, $places = null): array|string
    {
        if (is_array($value) || is_array($places)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $places);
        }

        try {
            $value = self::validateValue($value);
            $value = self::validateDecimal($value);
            $places = self::validatePlaces($places);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $value = (int) floor((float) $value);
        if ($value > self::LARGEST_OCTAL_IN_DECIMAL || $value < self::SMALLEST_OCTAL_IN_DECIMAL) {
            return ExcelError::NAN();
        }
        $r = decoct($value);
        $r = substr($r, -10);

        return self::nbrConversionFormat($r, $places);
    }

    protected static function validateDecimal(string $value): string
    {
        if (strlen($value) > preg_match_all('/[-0123456789.]/', $value)) {
            throw new Exception(ExcelError::VALUE());
        }

        return $value;
    }
}
