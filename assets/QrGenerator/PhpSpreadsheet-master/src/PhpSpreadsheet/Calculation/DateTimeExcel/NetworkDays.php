<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class NetworkDays
{
    use ArrayEnabled;

    
    public static function count(mixed $startDate, mixed $endDate, mixed ...$dateArgs): array|string|int
    {
        if (is_array($startDate) || is_array($endDate)) {
            return self::evaluateArrayArgumentsSubset(
                [self::class, __FUNCTION__],
                2,
                $startDate,
                $endDate,
                ...$dateArgs
            );
        }

        try {
            
            $sDate = Helpers::getDateValue($startDate);
            $eDate = Helpers::getDateValue($endDate);
            $startDate = min($sDate, $eDate);
            $endDate = max($sDate, $eDate);
            
            $dateArgs = Functions::flattenArray($dateArgs);
            
            $holidayArray = [];
            foreach ($dateArgs as $holidayDate) {
                $holidayArray[] = Helpers::getDateValue($holidayDate);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $startDow = self::calcStartDow($startDate);
        $endDow = self::calcEndDow($endDate);
        $wholeWeekDays = (int) floor(($endDate - $startDate) / 7) * 5;
        $partWeekDays = self::calcPartWeekDays($startDow, $endDow);

        
        $holidayCountedArray = [];
        foreach ($holidayArray as $holidayDate) {
            if (($holidayDate >= $startDate) && ($holidayDate <= $endDate)) {
                if ((Week::day($holidayDate, 2) < 6) && (!in_array($holidayDate, $holidayCountedArray))) {
                    --$partWeekDays;
                    $holidayCountedArray[] = $holidayDate;
                }
            }
        }

        return self::applySign($wholeWeekDays + $partWeekDays, $sDate, $eDate);
    }

    private static function calcStartDow(float $startDate): int
    {
        $startDow = 6 - (int) Week::day($startDate, 2);
        if ($startDow < 0) {
            $startDow = 5;
        }

        return $startDow;
    }

    private static function calcEndDow(float $endDate): int
    {
        $endDow = (int) Week::day($endDate, 2);
        if ($endDow >= 6) {
            $endDow = 0;
        }

        return $endDow;
    }

    private static function calcPartWeekDays(int $startDow, int $endDow): int
    {
        $partWeekDays = $endDow + $startDow;
        if ($partWeekDays > 5) {
            $partWeekDays -= 5;
        }

        return $partWeekDays;
    }

    private static function applySign(int $result, float $sDate, float $eDate): int
    {
        return ($sDate > $eDate) ? -$result : $result;
    }
}
