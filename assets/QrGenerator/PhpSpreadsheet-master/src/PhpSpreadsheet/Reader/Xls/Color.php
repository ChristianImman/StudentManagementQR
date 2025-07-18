<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

use PhpOffice\PhpSpreadsheet\Reader\Xls;

class Color
{
    
    public static function map(int $color, array $palette, int $version): array
    {
        if ($color <= 0x07 || $color >= 0x40) {
            
            return Color\BuiltIn::lookup($color);
        } elseif (isset($palette[$color - 8])) {
            
            return $palette[$color - 8];
        }

        return ($version === Xls::XLS_BIFF8) ? Color\BIFF8::lookup($color) : Color\BIFF5::lookup($color);
    }
}
