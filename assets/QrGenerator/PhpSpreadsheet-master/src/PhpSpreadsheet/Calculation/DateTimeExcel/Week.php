<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class Week
{
    use ArrayEnabled;

    
    public static function number(mixed $dateValue, array|int|string|null $method = Constants::STARTWEEK_SUNDAY): array|int|string
    {
        if (is_array($dateValue) || is_array($method)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $dateValue, $method);
        }

        $origDateValueNull = empty($dateValue);

        try {
            $method = self::validateMethod($method);
            if ($dateValue === null) { 
                $dateValue = (SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_MAC_1904 || $method === Constants::DOW_SUNDAY) ? 0 : 1;
            }
            $dateValue = self::validateDateValue($dateValue);
            if (!$dateValue && self::buggyWeekNum1900($method)) {
                
                return 0;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        if ($method == Constants::STARTWEEK_MONDAY_ISO) {
            Helpers::silly1900($PHPDateObject);

            return (int) $PHPDateObject->format('W');
        }
        if (self::buggyWeekNum1904($method, $origDateValueNull, $PHPDateObject)) {
            return 0;
        }
        Helpers::silly1900($PHPDateObject, '+ 5 years'); 
        $dayOfYear = (int) $PHPDateObject->format('z');
        $PHPDateObject->modify('-' . $dayOfYear . ' days');
        $firstDayOfFirstWeek = (int) $PHPDateObject->format('w');
        $daysInFirstWeek = (6 - $firstDayOfFirstWeek + $method) % 7;
        $daysInFirstWeek += 7 * !$daysInFirstWeek;
        $endFirstWeek = $daysInFirstWeek - 1;
        $weekOfYear = floor(($dayOfYear - $endFirstWeek + 13) / 7);

        return (int) $weekOfYear;
    }

    
    public static function isoWeekNumber(mixed $dateValue): array|int|string
    {
        if (is_array($dateValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $dateValue);
        }

        if (self::apparentBug($dateValue)) {
            return 52;
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        Helpers::silly1900($PHPDateObject);

        return (int) $PHPDateObject->format('W');
    }

    
    public static function day(null|array|float|int|string|bool $dateValue, mixed $style = 1): array|string|int
    {
        if (is_array($dateValue) || is_array($style)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $dateValue, $style);
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue);
            $style = self::validateStyle($style);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        Helpers::silly1900($PHPDateObject);
        $DoW = (int) $PHPDateObject->format('w');

        switch ($style) {
            case 1:
                ++$DoW;

                break;
            case 2:
                $DoW = self::dow0Becomes7($DoW);

                break;
            case 3:
                $DoW = self::dow0Becomes7($DoW) - 1;

                break;
        }

        return $DoW;
    }

    
    private static function validateStyle(mixed $style): int
    {
        if (!is_numeric($style)) {
            throw new Exception(ExcelError::VALUE());
        }
        $style = (int) $style;
        if (($style < 1) || ($style > 3)) {
            throw new Exception(ExcelError::NAN());
        }

        return $style;
    }

    private static function dow0Becomes7(int $DoW): int
    {
        return ($DoW === 0) ? 7 : $DoW;
    }

    
    private static function apparentBug(mixed $dateValue): bool
    {
        if (SharedDateHelper::getExcelCalendar() !== SharedDateHelper::CALENDAR_MAC_1904) {
            if (is_bool($dateValue)) {
                return true;
            }
            if (is_numeric($dateValue) && !((int) $dateValue)) {
                return true;
            }
        }

        return false;
    }

    
    private static function validateDateValue(mixed $dateValue): float
    {
        if (is_bool($dateValue)) {
            throw new Exception(ExcelError::VALUE());
        }

        return Helpers::getDateValue($dateValue);
    }

    
    private static function validateMethod(mixed $method): int
    {
        if ($method === null) {
            $method = Constants::STARTWEEK_SUNDAY;
        }

        if (!is_numeric($method)) {
            throw new Exception(ExcelError::VALUE());
        }

        $method = (int) $method;
        if (!array_key_exists($method, Constants::METHODARR)) {
            throw new Exception(ExcelError::NAN());
        }
        $method = Constants::METHODARR[$method];

        return $method;
    }

    private static function buggyWeekNum1900(int $method): bool
    {
        return $method === Constants::DOW_SUNDAY && SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_WINDOWS_1900;
    }

    private static function buggyWeekNum1904(int $method, bool $origNull, DateTime $dateObject): bool
    {
        

        return $method === Constants::DOW_SUNDAY && SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_MAC_1904
            && !$origNull && $dateObject->format('Y-m-d') === '1904-01-01';
    }
}
