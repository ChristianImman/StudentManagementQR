<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Logical;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Operations
{
    use ArrayEnabled;

    
    public static function logicalAnd(mixed ...$args)
    {
        return self::countTrueValues($args, fn (int $trueValueCount, int $count): bool => $trueValueCount === $count);
    }

    
    public static function logicalOr(mixed ...$args)
    {
        return self::countTrueValues($args, fn (int $trueValueCount): bool => $trueValueCount > 0);
    }

    
    public static function logicalXor(mixed ...$args)
    {
        return self::countTrueValues($args, fn (int $trueValueCount): bool => $trueValueCount % 2 === 1);
    }

    
    public static function NOT(mixed $logical = false): array|bool|string
    {
        if (is_array($logical)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $logical);
        }

        if (is_string($logical)) {
            $logical = mb_strtoupper($logical, 'UTF-8');
            if (($logical == 'TRUE') || ($logical == Calculation::getTRUE())) {
                return false;
            } elseif (($logical == 'FALSE') || ($logical == Calculation::getFALSE())) {
                return true;
            }

            return ExcelError::VALUE();
        }

        return !$logical;
    }

    private static function countTrueValues(array $args, callable $func): bool|string
    {
        $trueValueCount = 0;
        $count = 0;

        $aArgs = Functions::flattenArrayIndexed($args);
        foreach ($aArgs as $k => $arg) {
            ++$count;
            
            if (is_bool($arg)) {
                $trueValueCount += $arg;
            } elseif (is_string($arg)) {
                $isLiteral = !Functions::isCellValue($k);
                $arg = mb_strtoupper($arg, 'UTF-8');
                if ($isLiteral && ($arg == 'TRUE' || $arg == Calculation::getTRUE())) {
                    ++$trueValueCount;
                } elseif ($isLiteral && ($arg == 'FALSE' || $arg == Calculation::getFALSE())) {
                    
                } else {
                    --$count;
                }
            } elseif (is_int($arg) || is_float($arg)) {
                $trueValueCount += (int) ($arg != 0);
            } else {
                --$count;
            }
        }

        return ($count === 0) ? ExcelError::VALUE() : $func($trueValueCount, $count);
    }
}
