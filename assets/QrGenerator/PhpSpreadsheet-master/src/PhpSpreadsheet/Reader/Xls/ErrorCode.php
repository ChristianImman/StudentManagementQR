<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xls;

class ErrorCode
{
    private const ERROR_CODE_MAP = [
        0x00 => '
        0x07 => '
        0x0F => '
        0x17 => '
        0x1D => '
        0x24 => '
        0x2A => '
    ];

    
    public static function lookup(int $code): string|bool
    {
        return self::ERROR_CODE_MAP[$code] ?? false;
    }
}
