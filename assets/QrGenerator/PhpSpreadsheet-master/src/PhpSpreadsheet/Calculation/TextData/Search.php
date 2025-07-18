<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Search
{
    use ArrayEnabled;

    
    public static function sensitive(mixed $needle, mixed $haystack, mixed $offset = 1): array|string|int
    {
        if (is_array($needle) || is_array($haystack) || is_array($offset)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $needle, $haystack, $offset);
        }

        try {
            $needle = Helpers::extractString($needle, true);
            $haystack = Helpers::extractString($haystack, true);
            $offset = Helpers::extractInt($offset, 1, 0, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        if (StringHelper::countCharacters($haystack) >= $offset) {
            if (StringHelper::countCharacters($needle) === 0) {
                return $offset;
            }

            $pos = mb_strpos($haystack, $needle, --$offset, 'UTF-8');
            if ($pos !== false) {
                return ++$pos;
            }
        }

        return ExcelError::VALUE();
    }

    
    public static function insensitive(mixed $needle, mixed $haystack, mixed $offset = 1): array|string|int
    {
        if (is_array($needle) || is_array($haystack) || is_array($offset)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $needle, $haystack, $offset);
        }

        try {
            $needle = Helpers::extractString($needle, true);
            $haystack = Helpers::extractString($haystack, true);
            $offset = Helpers::extractInt($offset, 1, 0, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        if (StringHelper::countCharacters($haystack) >= $offset) {
            if (StringHelper::countCharacters($needle) === 0) {
                return $offset;
            }

            $pos = mb_stripos($haystack, $needle, --$offset, 'UTF-8');
            if ($pos !== false) {
                return ++$pos;
            }
        }

        return ExcelError::VALUE();
    }
}
