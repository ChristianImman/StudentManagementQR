<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Operations
{
    use ArrayEnabled;

    
    public static function mod(mixed $dividend, mixed $divisor): array|string|float
    {
        if (is_array($dividend) || is_array($divisor)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $dividend, $divisor);
        }

        try {
            $dividend = Helpers::validateNumericNullBool($dividend);
            $divisor = Helpers::validateNumericNullBool($divisor);
            Helpers::validateNotZero($divisor);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (($dividend < 0.0) && ($divisor > 0.0)) {
            return $divisor - fmod(abs($dividend), $divisor);
        }
        if (($dividend > 0.0) && ($divisor < 0.0)) {
            return $divisor + fmod($dividend, abs($divisor));
        }

        return fmod($dividend, $divisor);
    }

    
    public static function power(null|array|bool|float|int|string $x, null|array|bool|float|int|string $y): array|float|int|string
    {
        if (is_array($x) || is_array($y)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $x, $y);
        }

        try {
            $x = Helpers::validateNumericNullBool($x);
            $y = Helpers::validateNumericNullBool($y);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if (!$x && !$y) {
            return ExcelError::NAN();
        }
        if (!$x && $y < 0.0) {
            return ExcelError::DIV0();
        }

        
        $result = $x ** $y;

        return Helpers::numberOrNan($result);
    }

    
    public static function product(mixed ...$args): string|float
    {
        $args = array_filter(
            Functions::flattenArray($args),
            fn ($value): bool => $value !== null
        );

        
        $returnValue = (count($args) === 0) ? 0.0 : 1.0;

        
        foreach ($args as $arg) {
            
            if (is_numeric($arg)) {
                $returnValue *= $arg;
            } else {
                return ExcelError::throwError($arg);
            }
        }

        return (float) $returnValue;
    }

    
    public static function quotient(mixed $numerator, mixed $denominator): array|string|int
    {
        if (is_array($numerator) || is_array($denominator)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $numerator, $denominator);
        }

        try {
            $numerator = Helpers::validateNumericNullSubstitution($numerator, 0);
            $denominator = Helpers::validateNumericNullSubstitution($denominator, 0);
            Helpers::validateNotZero($denominator);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return (int) ($numerator / $denominator);
    }
}
