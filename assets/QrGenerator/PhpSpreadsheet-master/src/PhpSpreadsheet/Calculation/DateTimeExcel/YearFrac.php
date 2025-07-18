<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class YearFrac
{
    use ArrayEnabled;

    
    public static function fraction(mixed $startDate, mixed $endDate, array|int|string|null $method = 0): array|string|int|float
    {
        if (is_array($startDate) || is_array($endDate) || is_array($method)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $startDate, $endDate, $method);
        }

        try {
            $method = (int) Helpers::validateNumericNull($method);
            $sDate = Helpers::getDateValue($startDate);
            $eDate = Helpers::getDateValue($endDate);
            $sDate = self::excelBug($sDate, $startDate, $endDate, $method);
            $eDate = self::excelBug($eDate, $endDate, $startDate, $method);
            $startDate = min($sDate, $eDate);
            $endDate = max($sDate, $eDate);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return match ($method) {
            0 => Helpers::floatOrInt(Days360::between($startDate, $endDate)) / 360,
            1 => self::method1($startDate, $endDate),
            2 => Helpers::floatOrInt(Difference::interval($startDate, $endDate)) / 360,
            3 => Helpers::floatOrInt(Difference::interval($startDate, $endDate)) / 365,
            4 => Helpers::floatOrInt(Days360::between($startDate, $endDate, true)) / 360,
            default => ExcelError::NAN(),
        };
    }

    
    private static function excelBug(float $sDate, mixed $startDate, mixed $endDate, int $method): float
    {
        if (Functions::getCompatibilityMode() !== Functions::COMPATIBILITY_OPENOFFICE && SharedDateHelper::getExcelCalendar() !== SharedDateHelper::CALENDAR_MAC_1904) {
            if ($endDate === null && $startDate !== null) {
                if (DateParts::month($sDate) == 12 && DateParts::day($sDate) === 31 && $method === 0) {
                    $sDate += 2;
                } else {
                    ++$sDate;
                }
            }
        }

        return $sDate;
    }

    private static function method1(float $startDate, float $endDate): float
    {
        $days = Helpers::floatOrInt(Difference::interval($startDate, $endDate));
        $startYear = (int) DateParts::year($startDate);
        $endYear = (int) DateParts::year($endDate);
        $years = $endYear - $startYear + 1;
        $startMonth = (int) DateParts::month($startDate);
        $startDay = (int) DateParts::day($startDate);
        $endMonth = (int) DateParts::month($endDate);
        $endDay = (int) DateParts::day($endDate);
        $startMonthDay = 100 * $startMonth + $startDay;
        $endMonthDay = 100 * $endMonth + $endDay;
        if ($years == 1) {
            $tmpCalcAnnualBasis = 365 + (int) Helpers::isLeapYear($endYear);
        } elseif ($years == 2 && $startMonthDay >= $endMonthDay) {
            if (Helpers::isLeapYear($startYear)) {
                $tmpCalcAnnualBasis = 365 + (int) ($startMonthDay <= 229);
            } elseif (Helpers::isLeapYear($endYear)) {
                $tmpCalcAnnualBasis = 365 + (int) ($endMonthDay >= 229);
            } else {
                $tmpCalcAnnualBasis = 365;
            }
        } else {
            $tmpCalcAnnualBasis = 0;
            for ($year = $startYear; $year <= $endYear; ++$year) {
                $tmpCalcAnnualBasis += 365 + (int) Helpers::isLeapYear($year);
            }
            $tmpCalcAnnualBasis /= $years;
        }

        return $days / $tmpCalcAnnualBasis;
    }
}
