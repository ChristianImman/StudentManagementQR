<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class Days360
{
    use ArrayEnabled;

    
    public static function between(mixed $startDate = 0, mixed $endDate = 0, mixed $method = false): array|string|int
    {
        if (is_array($startDate) || is_array($endDate) || is_array($method)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $startDate, $endDate, $method);
        }

        try {
            $startDate = Helpers::getDateValue($startDate);
            $endDate = Helpers::getDateValue($endDate);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (!is_bool($method)) {
            return ExcelError::VALUE();
        }

        
        $PHPStartDateObject = SharedDateHelper::excelToDateTimeObject($startDate);
        $startDay = $PHPStartDateObject->format('j');
        $startMonth = $PHPStartDateObject->format('n');
        $startYear = $PHPStartDateObject->format('Y');

        $PHPEndDateObject = SharedDateHelper::excelToDateTimeObject($endDate);
        $endDay = $PHPEndDateObject->format('j');
        $endMonth = $PHPEndDateObject->format('n');
        $endYear = $PHPEndDateObject->format('Y');

        return self::dateDiff360((int) $startDay, (int) $startMonth, (int) $startYear, (int) $endDay, (int) $endMonth, (int) $endYear, !$method);
    }

    
    private static function dateDiff360(int $startDay, int $startMonth, int $startYear, int $endDay, int $endMonth, int $endYear, bool $methodUS): int
    {
        $startDay = self::getStartDay($startDay, $startMonth, $startYear, $methodUS);
        $endDay = self::getEndDay($endDay, $endMonth, $endYear, $startDay, $methodUS);

        return $endDay + $endMonth * 30 + $endYear * 360 - $startDay - $startMonth * 30 - $startYear * 360;
    }

    private static function getStartDay(int $startDay, int $startMonth, int $startYear, bool $methodUS): int
    {
        if ($startDay == 31) {
            --$startDay;
        } elseif ($methodUS && ($startMonth == 2 && ($startDay == 29 || ($startDay == 28 && !Helpers::isLeapYear($startYear))))) {
            $startDay = 30;
        }

        return $startDay;
    }

    private static function getEndDay(int $endDay, int &$endMonth, int &$endYear, int $startDay, bool $methodUS): int
    {
        if ($endDay == 31) {
            if ($methodUS && $startDay != 30) {
                $endDay = 1;
                if ($endMonth == 12) {
                    ++$endYear;
                    $endMonth = 1;
                } else {
                    ++$endMonth;
                }
            } else {
                $endDay = 30;
            }
        }

        return $endDay;
    }
}
