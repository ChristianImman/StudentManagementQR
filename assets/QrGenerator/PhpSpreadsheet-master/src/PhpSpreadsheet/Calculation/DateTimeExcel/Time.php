<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class Time
{
    use ArrayEnabled;

    
    public static function fromHMS(array|int|float|bool|null|string $hour, array|int|float|bool|null|string $minute, array|int|float|bool|null|string $second): array|string|float|int|DateTime
    {
        if (is_array($hour) || is_array($minute) || is_array($second)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $hour, $minute, $second);
        }

        try {
            $hour = self::toIntWithNullBool($hour);
            $minute = self::toIntWithNullBool($minute);
            $second = self::toIntWithNullBool($second);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        self::adjustSecond($second, $minute);
        self::adjustMinute($minute, $hour);

        if ($hour > 23) {
            $hour = $hour % 24;
        } elseif ($hour < 0) {
            return ExcelError::NAN();
        }

        
        $retType = Functions::getReturnDateType();
        if ($retType === Functions::RETURNDATE_EXCEL) {
            $calendar = SharedDateHelper::getExcelCalendar();
            $date = (int) ($calendar !== SharedDateHelper::CALENDAR_WINDOWS_1900);

            return (float) SharedDateHelper::formattedPHPToExcel($calendar, 1, $date, $hour, $minute, $second);
        }
        if ($retType === Functions::RETURNDATE_UNIX_TIMESTAMP) {
            return (int) SharedDateHelper::excelToTimestamp(SharedDateHelper::formattedPHPToExcel(1970, 1, 1, $hour, $minute, $second)); 
        }
        
        
        $phpDateObject = new DateTime('1900-01-01 ' . $hour . ':' . $minute . ':' . $second);

        return $phpDateObject;
    }

    private static function adjustSecond(int &$second, int &$minute): void
    {
        if ($second < 0) {
            $minute += (int) floor($second / 60);
            $second = 60 - abs($second % 60);
            if ($second == 60) {
                $second = 0;
            }
        } elseif ($second >= 60) {
            $minute += intdiv($second, 60);
            $second = $second % 60;
        }
    }

    private static function adjustMinute(int &$minute, int &$hour): void
    {
        if ($minute < 0) {
            $hour += (int) floor($minute / 60);
            $minute = 60 - abs($minute % 60);
            if ($minute == 60) {
                $minute = 0;
            }
        } elseif ($minute >= 60) {
            $hour += intdiv($minute, 60);
            $minute = $minute % 60;
        }
    }

    
    private static function toIntWithNullBool(mixed $value): int
    {
        $value = $value ?? 0;
        if (is_bool($value)) {
            $value = (int) $value;
        }
        if (!is_numeric($value)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) $value;
    }
}
