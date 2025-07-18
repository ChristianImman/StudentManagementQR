<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;

class Trim
{
    use ArrayEnabled;

    
    public static function nonPrintable(mixed $stringValue = '')
    {
        if (is_array($stringValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $stringValue);
        }

        $stringValue = Helpers::extractString($stringValue);

        return (string) preg_replace('/[\x00-\x1f]/', '', "$stringValue");
    }

    
    public static function spaces(mixed $stringValue = ''): array|string
    {
        if (is_array($stringValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $stringValue);
        }

        $stringValue = Helpers::extractString($stringValue);

        return trim(preg_replace('/ +/', ' ', trim("$stringValue", ' ')) ?? '', ' ');
    }
}
