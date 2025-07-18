<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xls;

class ErrorCode
{
    
    protected static array $errorCodeMap = [
        '
        '
        '
        '
        '
        '
        '
    ];

    public static function error(string $errorCode): int
    {
        if (array_key_exists($errorCode, self::$errorCodeMap)) {
            return self::$errorCodeMap[$errorCode];
        }

        return 0;
    }
}
