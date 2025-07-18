<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Information;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;

class ExcelError
{
    use ArrayEnabled;

    
    public const ERROR_CODES = [
        'null' => '
        'divisionbyzero' => '
        'value' => '
        'reference' => '
        'name' => '
        'num' => '
        'na' => '
        'gettingdata' => '
        'spill' => '
        'connect' => '
        'blocked' => '
        'unknown' => '
        'field' => '
        'calculation' => '
    ];

    public static function throwError(mixed $value): string
    {
        return in_array($value, self::ERROR_CODES, true) ? $value : self::ERROR_CODES['value'];
    }

    
    public static function type(mixed $value = ''): array|int|string
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        $i = 1;
        foreach (self::ERROR_CODES as $errorCode) {
            if ($value === $errorCode) {
                return $i;
            }
            ++$i;
        }

        return self::NA();
    }

    
    public static function null(): string
    {
        return self::ERROR_CODES['null'];
    }

    
    public static function NAN(): string
    {
        return self::ERROR_CODES['num'];
    }

    
    public static function REF(): string
    {
        return self::ERROR_CODES['reference'];
    }

    
    public static function NA(): string
    {
        return self::ERROR_CODES['na'];
    }

    
    public static function VALUE(): string
    {
        return self::ERROR_CODES['value'];
    }

    
    public static function NAME(): string
    {
        return self::ERROR_CODES['name'];
    }

    
    public static function DIV0(): string
    {
        return self::ERROR_CODES['divisionbyzero'];
    }

    
    public static function CALC(): string
    {
        return self::ERROR_CODES['calculation'];
    }

    
    public static function SPILL(): string
    {
        return self::ERROR_CODES['spill'];
    }
}
