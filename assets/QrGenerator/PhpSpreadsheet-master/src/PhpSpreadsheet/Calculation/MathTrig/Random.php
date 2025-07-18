<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Random
{
    use ArrayEnabled;

    
    public static function rand(): int|float
    {
        return mt_rand(0, 10000000) / 10000000;
    }

    
    public static function randBetween(mixed $min, mixed $max): array|string|int
    {
        if (is_array($min) || is_array($max)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $min, $max);
        }

        try {
            $min = (int) Helpers::validateNumericNullBool($min);
            $max = (int) Helpers::validateNumericNullBool($max);
            Helpers::validateNotNegative($max - $min);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return mt_rand($min, $max);
    }

    
    public static function randArray(mixed $rows = 1, mixed $columns = 1, mixed $min = 0, mixed $max = 1, bool $wholeNumber = false): string|array
    {
        try {
            $rows = (int) Helpers::validateNumericNullSubstitution($rows, 1);
            Helpers::validatePositive($rows);
            $columns = (int) Helpers::validateNumericNullSubstitution($columns, 1);
            Helpers::validatePositive($columns);
            $min = Helpers::validateNumericNullSubstitution($min, 1);
            $max = Helpers::validateNumericNullSubstitution($max, 1);

            if ($max <= $min) {
                return ExcelError::VALUE();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return array_chunk(
            array_map(
                fn (): int|float => $wholeNumber
                        ? mt_rand((int) $min, (int) $max)
                        : (mt_rand() / mt_getrandmax()) * ($max - $min) + $min,
                array_fill(0, $rows * $columns, $min)
            ),
            max($columns, 1)
        );
    }
}
