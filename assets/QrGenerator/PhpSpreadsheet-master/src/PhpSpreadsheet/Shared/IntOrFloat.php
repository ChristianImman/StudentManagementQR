<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

class IntOrFloat
{
    
    public static function evaluate(float|int $value): float|int
    {
        $iValue = (int) $value;

        return ($value == $iValue) ? $iValue : $value;
    }
}
