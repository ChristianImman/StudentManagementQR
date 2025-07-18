<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

use RoundingMode;

class Round
{
    use ArrayEnabled;

    
    public static function round(mixed $number, mixed $precision): array|string|float
    {
        if (is_array($number) || is_array($precision)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $precision);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            $precision = Helpers::validateNumericNullBool($precision);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return round($number, (int) $precision);
    }

    
    public static function up($number, $digits): array|string|float
    {
        if (is_array($number) || is_array($digits)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $digits);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            $digits = (int) Helpers::validateNumericNullSubstitution($digits, null);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($number == 0.0) {
            return 0.0;
        }

        if (PHP_VERSION_ID >= 80400) {
            return round(
                (float) (string) $number,
                $digits,
                RoundingMode::AwayFromZero /
    public static function down($number, $digits): array|string|float
    {
        if (is_array($number) || is_array($digits)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $digits);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
            $digits = (int) Helpers::validateNumericNullSubstitution($digits, null);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($number == 0.0) {
            return 0.0;
        }

        if (PHP_VERSION_ID >= 80400) {
            return round(
                (float) (string) $number,
                $digits,
                RoundingMode::TowardsZero /
    public static function multiple(mixed $number, mixed $multiple): array|string|int|float
    {
        if (is_array($number) || is_array($multiple)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $multiple);
        }

        try {
            $number = Helpers::validateNumericNullSubstitution($number, 0);
            $multiple = Helpers::validateNumericNullSubstitution($multiple, null);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($number == 0 || $multiple == 0) {
            return 0;
        }
        if ((Helpers::returnSign($number)) == (Helpers::returnSign($multiple))) {
            $multiplier = 1 / $multiple;

            return round($number * $multiplier) / $multiplier;
        }

        return ExcelError::NAN();
    }

    
    public static function even($number): array|string|float
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return Helpers::getEven($number);
    }

    
    public static function odd($number): array|string|int|float
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        try {
            $number = Helpers::validateNumericNullBool($number);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $significance = Helpers::returnSign($number);
        if ($significance == 0) {
            return 1;
        }

        $result = ceil($number / $significance) * $significance;
        if ($result == Helpers::getEven($result)) {
            $result += $significance;
        }

        return $result;
    }
}
