<?php

namespace PhpOffice\PhpSpreadsheet\Helper;

class Handler
{
    private static string $invalidHex = 'Y';

    
    
    public static function suppressed(): bool
    {
        return @trigger_error('hello');
    }

    public static function deprecated(): string
    {
        return (string) hexdec(self::$invalidHex);
    }

    public static function notice(string $value): void
    {
        date_default_timezone_set($value);
    }

    public static function warning(): bool
    {
        return file_get_contents(__FILE__ . 'noexist') !== false;
    }

    public static function userDeprecated(): bool
    {
        return trigger_error('hello', E_USER_DEPRECATED);
    }

    public static function userNotice(): bool
    {
        return trigger_error('userNotice', E_USER_NOTICE);
    }

    public static function userWarning(): bool
    {
        return trigger_error('userWarning', E_USER_WARNING);
    }
}
