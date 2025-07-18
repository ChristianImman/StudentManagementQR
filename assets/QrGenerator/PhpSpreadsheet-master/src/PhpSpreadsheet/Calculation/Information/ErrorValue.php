<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Information;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class ErrorValue
{
    use ArrayEnabled;

    
    public static function isErr(mixed $value = ''): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return self::isError($value) && (!self::isNa(($value)));
    }

    
    public static function isError(mixed $value = '', bool $tryNotImplemented = false): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if (!is_string($value)) {
            return false;
        }
        if ($tryNotImplemented && $value === Functions::NOT_YET_IMPLEMENTED) {
            return true;
        }

        return in_array($value, ExcelError::ERROR_CODES, true);
    }

    
    public static function isNa(mixed $value = ''): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return $value === ExcelError::NA();
    }
}
