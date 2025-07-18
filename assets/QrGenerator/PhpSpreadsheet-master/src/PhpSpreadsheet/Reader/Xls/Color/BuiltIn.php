<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls\Color;

class BuiltIn
{
    private const BUILTIN_COLOR_MAP = [
        0x00 => '000000',
        0x01 => 'FFFFFF',
        0x02 => 'FF0000',
        0x03 => '00FF00',
        0x04 => '0000FF',
        0x05 => 'FFFF00',
        0x06 => 'FF00FF',
        0x07 => '00FFFF',
        0x40 => '000000', 
        0x41 => 'FFFFFF', 
    ];

    
    public static function lookup(int $color): array
    {
        return ['rgb' => self::BUILTIN_COLOR_MAP[$color] ?? '000000'];
    }
}
