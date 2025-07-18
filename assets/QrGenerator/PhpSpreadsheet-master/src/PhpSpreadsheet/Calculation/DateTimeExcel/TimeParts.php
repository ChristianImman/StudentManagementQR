<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class TimeParts
{
    use ArrayEnabled;

    
    public static function hour(mixed $timeValue): array|string|int
    {
        if (is_array($timeValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $timeValue);
        }

        try {
            Helpers::nullFalseTrueToNumber($timeValue);
            if (is_string($timeValue) && !is_numeric($timeValue)) {
                $timeValue = Helpers::getTimeValue($timeValue);
            }
            Helpers::validateNotNegative($timeValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $timeValue = fmod($timeValue, 1);
        $timeValue = SharedDateHelper::excelToDateTimeObject($timeValue);
        SharedDateHelper::roundMicroseconds($timeValue);

        return (int) $timeValue->format('H');
    }

    
    public static function minute(mixed $timeValue): array|string|int
    {
        if (is_array($timeValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $timeValue);
        }

        try {
            Helpers::nullFalseTrueToNumber($timeValue);
            if (is_string($timeValue) && !is_numeric($timeValue)) {
                $timeValue = Helpers::getTimeValue($timeValue);
            }
            Helpers::validateNotNegative($timeValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $timeValue = fmod($timeValue, 1);
        $timeValue = SharedDateHelper::excelToDateTimeObject($timeValue);
        SharedDateHelper::roundMicroseconds($timeValue);

        return (int) $timeValue->format('i');
    }

    
    public static function second(mixed $timeValue): array|string|int
    {
        if (is_array($timeValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $timeValue);
        }

        try {
            Helpers::nullFalseTrueToNumber($timeValue);
            if (is_string($timeValue) && !is_numeric($timeValue)) {
                $timeValue = Helpers::getTimeValue($timeValue);
            }
            Helpers::validateNotNegative($timeValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $timeValue = fmod($timeValue, 1);
        $timeValue = SharedDateHelper::excelToDateTimeObject($timeValue);
        SharedDateHelper::roundMicroseconds($timeValue);

        return (int) $timeValue->format('s');
    }
}
