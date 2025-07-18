<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class WorkDay
{
    use ArrayEnabled;

    
    public static function date(mixed $startDate, array|int|string $endDays, mixed ...$dateArgs): array|float|int|DateTime|string
    {
        if (is_array($startDate) || is_array($endDays)) {
            return self::evaluateArrayArgumentsSubset(
                [self::class, __FUNCTION__],
                2,
                $startDate,
                $endDays,
                ...$dateArgs
            );
        }

        
        try {
            $startDate = Helpers::getDateValue($startDate);
            $endDays = Helpers::validateNumericNull($endDays);
            $holidayArray = array_map([Helpers::class, 'getDateValue'], Functions::flattenArray($dateArgs));
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $startDate = (float) floor($startDate);
        $endDays = (int) floor($endDays);
        
        if ($endDays == 0) {
            return $startDate;
        }
        if ($endDays < 0) {
            return self::decrementing($startDate, $endDays, $holidayArray);
        }

        return self::incrementing($startDate, $endDays, $holidayArray);
    }

    
    private static function incrementing(float $startDate, int $endDays, array $holidayArray): float|int|DateTime
    {
        
        $startDoW = self::getWeekDay($startDate, 3);
        if ($startDoW >= 5) {
            $startDate += 7 - $startDoW;
            --$endDays;
        }

        
        $endDate = (float) $startDate + ((int) ($endDays / 5) * 7);
        $endDays = $endDays % 5;
        while ($endDays > 0) {
            ++$endDate;
            
            $endDow = self::getWeekDay($endDate, 3);
            if ($endDow >= 5) {
                $endDate += 7 - $endDow;
            }
            --$endDays;
        }

        
        if (!empty($holidayArray)) {
            $endDate = self::incrementingArray($startDate, $endDate, $holidayArray);
        }

        return Helpers::returnIn3FormatsFloat($endDate);
    }

    private static function incrementingArray(float $startDate, float $endDate, array $holidayArray): float
    {
        $holidayCountedArray = $holidayDates = [];
        foreach ($holidayArray as $holidayDate) {
            if (self::getWeekDay($holidayDate, 3) < 5) {
                $holidayDates[] = $holidayDate;
            }
        }
        sort($holidayDates, SORT_NUMERIC);
        foreach ($holidayDates as $holidayDate) {
            if (($holidayDate >= $startDate) && ($holidayDate <= $endDate)) {
                if (!in_array($holidayDate, $holidayCountedArray)) {
                    ++$endDate;
                    $holidayCountedArray[] = $holidayDate;
                }
            }
            
            $endDoW = self::getWeekDay($endDate, 3);
            if ($endDoW >= 5) {
                $endDate += 7 - $endDoW;
            }
        }

        return $endDate;
    }

    
    private static function decrementing(float $startDate, int $endDays, array $holidayArray): float|int|DateTime
    {
        
        $startDoW = self::getWeekDay($startDate, 3);
        if ($startDoW >= 5) {
            $startDate += -$startDoW + 4;
            ++$endDays;
        }

        
        $endDate = (float) $startDate + ((int) ($endDays / 5) * 7);
        $endDays = $endDays % 5;
        while ($endDays < 0) {
            --$endDate;
            
            $endDow = self::getWeekDay($endDate, 3);
            if ($endDow >= 5) {
                $endDate += 4 - $endDow;
            }
            ++$endDays;
        }

        
        if (!empty($holidayArray)) {
            $endDate = self::decrementingArray($startDate, $endDate, $holidayArray);
        }

        return Helpers::returnIn3FormatsFloat($endDate);
    }

    private static function decrementingArray(float $startDate, float $endDate, array $holidayArray): float
    {
        $holidayCountedArray = $holidayDates = [];
        foreach ($holidayArray as $holidayDate) {
            if (self::getWeekDay($holidayDate, 3) < 5) {
                $holidayDates[] = $holidayDate;
            }
        }
        rsort($holidayDates, SORT_NUMERIC);
        foreach ($holidayDates as $holidayDate) {
            if (($holidayDate <= $startDate) && ($holidayDate >= $endDate)) {
                if (!in_array($holidayDate, $holidayCountedArray)) {
                    --$endDate;
                    $holidayCountedArray[] = $holidayDate;
                }
            }
            
            $endDoW = self::getWeekDay($endDate, 3);
            
            if ($endDoW >= 5) {
                $endDate += -$endDoW + 4;
            }
        }

        return $endDate;
    }

    private static function getWeekDay(float $date, int $wd): int
    {
        $result = Functions::scalar(Week::day($date, $wd));

        return is_int($result) ? $result : -1;
    }
}
