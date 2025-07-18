<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class Helpers
{
    
    public static function isLeapYear(int|string $year): bool
    {
        $year = (int) $year;

        return (($year % 4) === 0) && (($year % 100) !== 0) || (($year % 400) === 0);
    }

    
    public static function getDateValue(mixed $dateValue, bool $allowBool = true): float
    {
        if (is_object($dateValue)) {
            $retval = SharedDateHelper::PHPToExcel($dateValue);
            if (is_bool($retval)) {
                throw new Exception(ExcelError::VALUE());
            }

            return $retval;
        }

        self::nullFalseTrueToNumber($dateValue, $allowBool);
        if (!is_numeric($dateValue)) {
            $saveReturnDateType = Functions::getReturnDateType();
            Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);
            if (is_string($dateValue)) {
                $dateValue = DateValue::fromString($dateValue);
            }
            Functions::setReturnDateType($saveReturnDateType);
            if (!is_numeric($dateValue)) {
                throw new Exception(ExcelError::VALUE());
            }
        }
        if ($dateValue < 0 && Functions::getCompatibilityMode() !== Functions::COMPATIBILITY_OPENOFFICE) {
            throw new Exception(ExcelError::NAN());
        }

        return (float) $dateValue;
    }

    
    public static function getTimeValue(string $timeValue): string|float
    {
        $saveReturnDateType = Functions::getReturnDateType();
        Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);
        
        $timeValue = TimeValue::fromString($timeValue);
        Functions::setReturnDateType($saveReturnDateType);

        return $timeValue;
    }

    
    public static function adjustDateByMonths($dateValue = 0, float $adjustmentMonths = 0): DateTime
    {
        
        $PHPDateObject = SharedDateHelper::excelToDateTimeObject($dateValue);
        $oMonth = (int) $PHPDateObject->format('m');
        $oYear = (int) $PHPDateObject->format('Y');

        $adjustmentMonthsString = (string) $adjustmentMonths;
        if ($adjustmentMonths > 0) {
            $adjustmentMonthsString = '+' . $adjustmentMonths;
        }
        if ($adjustmentMonths != 0) {
            $PHPDateObject->modify($adjustmentMonthsString . ' months');
        }
        $nMonth = (int) $PHPDateObject->format('m');
        $nYear = (int) $PHPDateObject->format('Y');

        $monthDiff = ($nMonth - $oMonth) + (($nYear - $oYear) * 12);
        if ($monthDiff != $adjustmentMonths) {
            $adjustDays = (int) $PHPDateObject->format('d');
            $adjustDaysString = '-' . $adjustDays . ' days';
            $PHPDateObject->modify($adjustDaysString);
        }

        return $PHPDateObject;
    }

    
    public static function replaceIfEmpty(mixed &$value, mixed $altValue): void
    {
        $value = $value ?: $altValue;
    }

    
    public static function adjustYear(string $testVal1, string $testVal2, string &$testVal3): void
    {
        if (!is_numeric($testVal1) || $testVal1 < 31) {
            if (!is_numeric($testVal2) || $testVal2 < 12) {
                if (is_numeric($testVal3) && $testVal3 < 12) {
                    $testVal3 = (string) ($testVal3 + 2000);
                }
            }
        }
    }

    
    public static function returnIn3FormatsArray(array $dateArray, bool $noFrac = false): DateTime|float|int
    {
        $retType = Functions::getReturnDateType();
        if ($retType === Functions::RETURNDATE_PHP_DATETIME_OBJECT) {
            return new DateTime(
                $dateArray['year']
                . '-' . $dateArray['month']
                . '-' . $dateArray['day']
                . ' ' . $dateArray['hour']
                . ':' . $dateArray['minute']
                . ':' . $dateArray['second']
            );
        }
        $excelDateValue
            = SharedDateHelper::formattedPHPToExcel(
                $dateArray['year'],
                $dateArray['month'],
                $dateArray['day'],
                $dateArray['hour'],
                $dateArray['minute'],
                $dateArray['second']
            );
        if ($retType === Functions::RETURNDATE_EXCEL) {
            return $noFrac ? floor($excelDateValue) : $excelDateValue;
        }
        

        return SharedDateHelper::excelToTimestamp($excelDateValue);
    }

    
    public static function returnIn3FormatsFloat(float $excelDateValue): float|int|DateTime
    {
        $retType = Functions::getReturnDateType();
        if ($retType === Functions::RETURNDATE_EXCEL) {
            return $excelDateValue;
        }
        if ($retType === Functions::RETURNDATE_UNIX_TIMESTAMP) {
            return SharedDateHelper::excelToTimestamp($excelDateValue);
        }
        

        return SharedDateHelper::excelToDateTimeObject($excelDateValue);
    }

    
    public static function returnIn3FormatsObject(DateTime $PHPDateObject): DateTime|float|int
    {
        $retType = Functions::getReturnDateType();
        if ($retType === Functions::RETURNDATE_PHP_DATETIME_OBJECT) {
            return $PHPDateObject;
        }
        if ($retType === Functions::RETURNDATE_EXCEL) {
            return (float) SharedDateHelper::PHPToExcel($PHPDateObject);
        }
        
        $stamp = SharedDateHelper::PHPToExcel($PHPDateObject);
        $stamp = is_bool($stamp) ? ((int) $stamp) : $stamp;

        return SharedDateHelper::excelToTimestamp($stamp);
    }

    private static function baseDate(): int
    {
        if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE) {
            return 0;
        }
        if (SharedDateHelper::getExcelCalendar() === SharedDateHelper::CALENDAR_MAC_1904) {
            return 0;
        }

        return 1;
    }

    
    public static function nullFalseTrueToNumber(mixed &$number, bool $allowBool = true): void
    {
        $number = Functions::flattenSingleValue($number);
        $nullVal = self::baseDate();
        if ($number === null) {
            $number = $nullVal;
        } elseif ($allowBool && is_bool($number)) {
            $number = $nullVal + (int) $number;
        }
    }

    
    public static function validateNumericNull(mixed $number): int|float
    {
        $number = Functions::flattenSingleValue($number);
        if ($number === null) {
            return 0;
        }
        if (is_int($number)) {
            return $number;
        }
        if (is_numeric($number)) {
            return (float) $number;
        }

        throw new Exception(ExcelError::VALUE());
    }

    
    public static function validateNotNegative(mixed $number): float
    {
        if (!is_numeric($number)) {
            throw new Exception(ExcelError::VALUE());
        }
        if ($number >= 0) {
            return (float) $number;
        }

        throw new Exception(ExcelError::NAN());
    }

    public static function silly1900(DateTime $PHPDateObject, string $mod = '-1 day'): void
    {
        $isoDate = $PHPDateObject->format('c');
        if ($isoDate < '1900-03-01') {
            $PHPDateObject->modify($mod);
        }
    }

    public static function dateParse(string $string): array
    {
        return self::forceArray(date_parse($string));
    }

    public static function dateParseSucceeded(array $dateArray): bool
    {
        return $dateArray['error_count'] === 0;
    }

    
    private static function forceArray(array|bool $dateArray): array
    {
        return is_array($dateArray) ? $dateArray : ['error_count' => 1];
    }

    public static function floatOrInt(mixed $value): float|int
    {
        $result = Functions::scalar($value);

        return is_numeric($result) ? ($result + 0) : 0;
    }
}
