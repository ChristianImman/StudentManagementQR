<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Date
{
    use ArrayEnabled;

    
    public static function fromYMD(array|float|int|string $year, null|array|bool|float|int|string $month, array|float|int|string $day): float|int|DateTime|string|array
    {
        if (is_array($year) || is_array($month) || is_array($day)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $year, $month, $day);
        }

        $baseYear = SharedDateHelper::getExcelCalendar();

        try {
            $year = self::getYear($year, $baseYear);
            $month = self::getMonth($month);
            $day = self::getDay($day);
            self::adjustYearMonth($year, $month, $baseYear);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $excelDateValue = SharedDateHelper::formattedPHPToExcel($year, $month, $day);

        return Helpers::returnIn3FormatsFloat($excelDateValue);
    }

    
    private static function getYear(mixed $year, int $baseYear): int
    {
        if ($year === null) {
            $year = 0;
        } elseif (is_scalar($year)) {
            $year = StringHelper::testStringAsNumeric((string) $year);
        }
        if (!is_numeric($year)) {
            throw new Exception(ExcelError::VALUE());
        }
        $year = (int) $year;

        if ($year < ($baseYear - 1900)) {
            throw new Exception(ExcelError::NAN());
        }
        if ((($baseYear - 1900) !== 0) && ($year < $baseYear) && ($year >= 1900)) {
            throw new Exception(ExcelError::NAN());
        }

        if (($year < $baseYear) && ($year >= ($baseYear - 1900))) {
            $year += 1900;
        }

        return (int) $year;
    }

    
    private static function getMonth(mixed $month): int
    {
        if (is_string($month)) {
            if (!is_numeric($month)) {
                $month = SharedDateHelper::monthStringToNumber($month);
            }
        } elseif ($month === null) {
            $month = 0;
        } elseif (is_bool($month)) {
            $month = (int) $month;
        }
        if (!is_numeric($month)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) $month;
    }

    
    private static function getDay(mixed $day): int
    {
        if (is_string($day) && !is_numeric($day)) {
            $day = SharedDateHelper::dayStringToNumber($day);
        }

        if ($day === null) {
            $day = 0;
        } elseif (is_scalar($day)) {
            $day = StringHelper::testStringAsNumeric((string) $day);
        }
        if (!is_numeric($day)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) $day;
    }

    private static function adjustYearMonth(int &$year, int &$month, int $baseYear): void
    {
        if ($month < 1) {
            
            --$month;
            $year += (int) (ceil($month / 12) - 1);
            $month = 13 - abs($month % 12);
        } elseif ($month > 12) {
            
            $year += intdiv($month, 12);
            $month = ($month % 12);
        }

        
        if (($year < $baseYear) || ($year >= 10000)) {
            throw new Exception(ExcelError::NAN());
        }
    }
}
