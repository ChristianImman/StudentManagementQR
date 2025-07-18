<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Month
{
    use ArrayEnabled;

    
    public static function adjust(mixed $dateValue, array|string|bool|float|int $adjustmentMonths): DateTime|float|int|string|array
    {
        if (is_array($dateValue) || is_array($adjustmentMonths)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $dateValue, $adjustmentMonths);
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue, false);
            $adjustmentMonths = Helpers::validateNumericNull($adjustmentMonths);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        $dateValue = floor($dateValue);
        $adjustmentMonths = floor($adjustmentMonths);

        
        $PHPDateObject = Helpers::adjustDateByMonths($dateValue, $adjustmentMonths);

        return Helpers::returnIn3FormatsObject($PHPDateObject);
    }

    
    public static function lastDay(mixed $dateValue, array|float|int|bool|string $adjustmentMonths): array|string|DateTime|float|int
    {
        if (is_array($dateValue) || is_array($adjustmentMonths)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $dateValue, $adjustmentMonths);
        }

        try {
            $dateValue = Helpers::getDateValue($dateValue, false);
            $adjustmentMonths = Helpers::validateNumericNull($adjustmentMonths);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        $dateValue = floor($dateValue);
        $adjustmentMonths = floor($adjustmentMonths);

        
        $PHPDateObject = Helpers::adjustDateByMonths($dateValue, $adjustmentMonths + 1);
        $adjustDays = (int) $PHPDateObject->format('d');
        $adjustDaysString = '-' . $adjustDays . ' days';
        $PHPDateObject->modify($adjustDaysString);

        return Helpers::returnIn3FormatsObject($PHPDateObject);
    }
}
