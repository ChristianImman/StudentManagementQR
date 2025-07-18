<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

class StandardDeviations
{
    
    public static function STDEV(mixed ...$args)
    {
        $result = Variances::VAR(...$args);
        if (!is_numeric($result)) {
            return $result;
        }

        return sqrt((float) $result);
    }

    
    public static function STDEVA(mixed ...$args): float|string
    {
        $result = Variances::VARA(...$args);
        if (!is_numeric($result)) {
            return $result;
        }

        return sqrt((float) $result);
    }

    
    public static function STDEVP(mixed ...$args): float|string
    {
        $result = Variances::VARP(...$args);
        if (!is_numeric($result)) {
            return $result;
        }

        return sqrt((float) $result);
    }

    
    public static function STDEVPA(mixed ...$args): float|string
    {
        $result = Variances::VARPA(...$args);
        if (!is_numeric($result)) {
            return $result;
        }

        return sqrt((float) $result);
    }
}
