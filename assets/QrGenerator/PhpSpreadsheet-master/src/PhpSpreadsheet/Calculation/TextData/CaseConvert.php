<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class CaseConvert
{
    use ArrayEnabled;

    
    public static function lower(mixed $mixedCaseValue): array|string
    {
        if (is_array($mixedCaseValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $mixedCaseValue);
        }

        try {
            $mixedCaseValue = Helpers::extractString($mixedCaseValue, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        return StringHelper::strToLower($mixedCaseValue);
    }

    
    public static function upper(mixed $mixedCaseValue): array|string
    {
        if (is_array($mixedCaseValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $mixedCaseValue);
        }

        try {
            $mixedCaseValue = Helpers::extractString($mixedCaseValue, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        return StringHelper::strToUpper($mixedCaseValue);
    }

    
    public static function proper(mixed $mixedCaseValue): array|string
    {
        if (is_array($mixedCaseValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $mixedCaseValue);
        }

        try {
            $mixedCaseValue = Helpers::extractString($mixedCaseValue, true);
        } catch (CalcExp $e) {
            return $e->getMessage();
        }

        return StringHelper::strToTitle($mixedCaseValue);
    }
}
