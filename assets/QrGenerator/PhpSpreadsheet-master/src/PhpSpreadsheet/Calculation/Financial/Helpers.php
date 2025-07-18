<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Helpers
{
    
    public static function daysPerYear(mixed $year, $basis = 0): string|int
    {
        if (!is_int($year) && !is_string($year)) {
            return ExcelError::VALUE();
        }
        if (!is_numeric($basis)) {
            return ExcelError::NAN();
        }

        switch ($basis) {
            case FinancialConstants::BASIS_DAYS_PER_YEAR_NASD:
            case FinancialConstants::BASIS_DAYS_PER_YEAR_360:
            case FinancialConstants::BASIS_DAYS_PER_YEAR_360_EUROPEAN:
                return 360;
            case FinancialConstants::BASIS_DAYS_PER_YEAR_365:
                return 365;
            case FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL:
                return (DateTimeExcel\Helpers::isLeapYear($year)) ? 366 : 365;
        }

        return ExcelError::NAN();
    }

    
    public static function isLastDayOfMonth(DateTimeInterface $date): bool
    {
        return $date->format('d') === $date->format('t');
    }
}
