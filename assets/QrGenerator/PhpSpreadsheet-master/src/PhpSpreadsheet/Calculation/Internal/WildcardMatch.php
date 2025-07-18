<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Internal;

class WildcardMatch
{
    private const SEARCH_SET = [
        '~~', 
        '~\*', 
        '\*', 
        '~\?', 
        '\?', 
        "\x1c", 
    ];

    private const REPLACEMENT_SET = [
        "\x1c",
        '[*]',
        '.*',
        '[?]',
        '.',
        '~',
    ];

    public static function wildcard(string $wildcard): string
    {
        
        return str_replace(self::SEARCH_SET, self::REPLACEMENT_SET, preg_quote($wildcard, '/'));
    }

    public static function compare(?string $value, string $wildcard): bool
    {
        if ($value === '' || $value === null) {
            return false;
        }

        return (bool) preg_match("/^{$wildcard}\$/mui", $value);
    }
}
