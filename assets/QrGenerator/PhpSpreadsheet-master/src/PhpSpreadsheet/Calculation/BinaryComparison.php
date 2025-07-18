<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class BinaryComparison
{
    
    private const DELTA = 0.1e-12;

    
    private static function strcmpLowercaseFirst(mixed $str1, mixed $str2): int
    {
        $str1 = StringHelper::convertToString($str1);
        $str2 = StringHelper::convertToString($str2);
        $inversedStr1 = StringHelper::strCaseReverse($str1);
        $inversedStr2 = StringHelper::strCaseReverse($str2);

        return strcmp($inversedStr1, $inversedStr2);
    }

    
    private static function strcmpAllowNull(mixed $str1, mixed $str2): int
    {
        $str1 = StringHelper::convertToString($str1);
        $str2 = StringHelper::convertToString($str2);

        return strcmp($str1, $str2);
    }

    public static function compare(mixed $operand1, mixed $operand2, string $operator): bool
    {
        
        if (is_string($operand1) && $operand1 > '' && $operand1[0] == Calculation::FORMULA_STRING_QUOTE) {
            $operand1 = Calculation::unwrapResult($operand1);
        }
        if (is_string($operand2) && $operand2 > '' && $operand2[0] == Calculation::FORMULA_STRING_QUOTE) {
            $operand2 = Calculation::unwrapResult($operand2);
        }

        
        if (Functions::getCompatibilityMode() != Functions::COMPATIBILITY_OPENOFFICE) {
            if (is_string($operand1)) {
                $operand1 = StringHelper::strToUpper($operand1);
            }
            if (is_string($operand2)) {
                $operand2 = StringHelper::strToUpper($operand2);
            }
        }

        $useLowercaseFirstComparison = is_string($operand1)
            && is_string($operand2)
            && Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE;

        return self::evaluateComparison($operand1, $operand2, $operator, $useLowercaseFirstComparison);
    }

    private static function evaluateComparison(mixed $operand1, mixed $operand2, string $operator, bool $useLowercaseFirstComparison): bool
    {
        return match ($operator) {
            '=' => self::equal($operand1, $operand2),
            '>' => self::greaterThan($operand1, $operand2, $useLowercaseFirstComparison),
            '<' => self::lessThan($operand1, $operand2, $useLowercaseFirstComparison),
            '>=' => self::greaterThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison),
            '<=' => self::lessThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison),
            '<>' => self::notEqual($operand1, $operand2),
            default => throw new Exception('Unsupported binary comparison operator'),
        };
    }

    private static function equal(mixed $operand1, mixed $operand2): bool
    {
        if (is_numeric($operand1) && is_numeric($operand2)) {
            $result = (abs($operand1 - $operand2) < self::DELTA);
        } elseif (($operand1 === null && is_numeric($operand2)) || ($operand2 === null && is_numeric($operand1))) {
            $result = $operand1 == $operand2;
        } else {
            $result = self::strcmpAllowNull($operand1, $operand2) == 0;
        }

        return $result;
    }

    private static function greaterThanOrEqual(mixed $operand1, mixed $operand2, bool $useLowercaseFirstComparison): bool
    {
        if (is_numeric($operand1) && is_numeric($operand2)) {
            $result = ((abs($operand1 - $operand2) < self::DELTA) || ($operand1 > $operand2));
        } elseif (($operand1 === null && is_numeric($operand2)) || ($operand2 === null && is_numeric($operand1))) {
            $result = $operand1 >= $operand2;
        } elseif ($useLowercaseFirstComparison) {
            $result = self::strcmpLowercaseFirst($operand1, $operand2) >= 0;
        } else {
            $result = self::strcmpAllowNull($operand1, $operand2) >= 0;
        }

        return $result;
    }

    private static function lessThanOrEqual(mixed $operand1, mixed $operand2, bool $useLowercaseFirstComparison): bool
    {
        if (is_numeric($operand1) && is_numeric($operand2)) {
            $result = ((abs($operand1 - $operand2) < self::DELTA) || ($operand1 < $operand2));
        } elseif (($operand1 === null && is_numeric($operand2)) || ($operand2 === null && is_numeric($operand1))) {
            $result = $operand1 <= $operand2;
        } elseif ($useLowercaseFirstComparison) {
            $result = self::strcmpLowercaseFirst($operand1, $operand2) <= 0;
        } else {
            $result = self::strcmpAllowNull($operand1, $operand2) <= 0;
        }

        return $result;
    }

    private static function greaterThan(mixed $operand1, mixed $operand2, bool $useLowercaseFirstComparison): bool
    {
        return self::lessThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison) !== true;
    }

    private static function lessThan(mixed $operand1, mixed $operand2, bool $useLowercaseFirstComparison): bool
    {
        return self::greaterThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison) !== true;
    }

    private static function notEqual(mixed $operand1, mixed $operand2): bool
    {
        return self::equal($operand1, $operand2) !== true;
    }
}
