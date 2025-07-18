<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class DateParts
{
    use ArrayEnabled;

    
    public static function day(mixed $dateValue): array|int|string
    {
        if (is_array($dateValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $dateValue);
        }

        $weirdResult = self::weirdCondition($dateValue);
        if ($weirdResult >= 0) {
            return $weirdResult;
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        SharedDateHelper::roundMicroseconds($PHPDateObject);

        return (int) $PHPDateObject->format('j');
    }

    
    public static function month(mixed $dateValue): array|string|int
    {
        if (is_array($dateValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $dateValue);
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        if ($dateValue < 1 && SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_WINDOWS_1900) {
            return 1;
        }

        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        SharedDateHelper::roundMicroseconds($PHPDateObject);

        return (int) $PHPDateObject->format('n');
    }

    
    public static function year(mixed $dateValue): array|string|int
    {
        if (is_array($dateValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $dateValue);
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($dateValue < 1 && SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_WINDOWS_1900) {
            return 1900;
        }
        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        SharedDateHelper::roundMicroseconds($PHPDateObject);

        return (int) $PHPDateObject->format('Y');
    }

    
    private static function weirdCondition(mixed $dateValue): int
    {
        
        if (SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_WINDOWS_1900 && Functions::getCompatibilityMode() == Functions::COMPATIBILITY_EXCEL) {
            if (is_bool($dateValue)) {
                return (int) $dateValue;
            }
            if ($dateValue === null) {
                return 0;
            }
            if (is_numeric($dateValue) && $dateValue < 1 && $dateValue >= 0) {
                return 0;
            }
        }

        return -1;
    }
}
