<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class Days
{
    use ArrayEnabled;

    
    public static function between(array|DateTimeInterface|float|int|string $endDate, array|DateTimeInterface|float|int|string $startDate): array|int|string
    {
        if (is_array($endDate) || is_array($startDate)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $endDate, $startDate);
        }

        try {
            $startDate = Helpers::getDateValue($startDate);
            $endDate = Helpers::getDateValue($endDate);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $PHPStartDateObject = SharedDateHelper::excelToDateTimeObject($startDate);
        $PHPEndDateObject = SharedDateHelper::excelToDateTimeObject($endDate);

        $days = ExcelError::VALUE();
        $diff = $PHPStartDateObject->diff($PHPEndDateObject);
        if (!is_bool($diff->days)) {
            $days = $diff->days;
            if ($diff->invert) {
                $days = -$days;
            }
        }

        return $days;
    }
}
