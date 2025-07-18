<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Coupons
{
    private const PERIOD_DATE_PREVIOUS = false;
    private const PERIOD_DATE_NEXT = true;

    
    public static function COUPDAYBS(
        mixed $settlement,
        mixed $maturity,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|int|float {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            self::validateCouponPeriod($settlement, $maturity);
            $frequency = FinancialValidations::validateFrequency($frequency);
            $basis = FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $daysPerYear = Helpers::daysPerYear(Functions::scalar(DateTimeExcel\DateParts::year($settlement)), $basis);
        if (is_string($daysPerYear)) {
            return ExcelError::VALUE();
        }
        $prev = self::couponFirstPeriodDate($settlement, $maturity, $frequency, self::PERIOD_DATE_PREVIOUS);

        if ($basis === FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL) {
            return abs((float) DateTimeExcel\Days::between($prev, $settlement));
        }

        return (float) DateTimeExcel\YearFrac::fraction($prev, $settlement, $basis) * $daysPerYear;
    }

    
    public static function COUPDAYS(
        mixed $settlement,
        mixed $maturity,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|int|float {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            self::validateCouponPeriod($settlement, $maturity);
            $frequency = FinancialValidations::validateFrequency($frequency);
            $basis = FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        switch ($basis) {
            case FinancialConstants::BASIS_DAYS_PER_YEAR_365:
                
                return 365 / $frequency;
            case FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL:
                
                if ($frequency == FinancialConstants::FREQUENCY_ANNUAL) {
                    $daysPerYear = (int) Helpers::daysPerYear(Functions::scalar(DateTimeExcel\DateParts::year($settlement)), $basis);

                    return $daysPerYear / $frequency;
                }
                $prev = self::couponFirstPeriodDate($settlement, $maturity, $frequency, self::PERIOD_DATE_PREVIOUS);
                $next = self::couponFirstPeriodDate($settlement, $maturity, $frequency, self::PERIOD_DATE_NEXT);

                return $next - $prev;
            default:
                
                return 360 / $frequency;
        }
    }

    
    public static function COUPDAYSNC(
        mixed $settlement,
        mixed $maturity,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|float {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            self::validateCouponPeriod($settlement, $maturity);
            $frequency = FinancialValidations::validateFrequency($frequency);
            $basis = FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        $daysPerYear = Helpers::daysPerYear(Functions::Scalar(DateTimeExcel\DateParts::year($settlement)), $basis);
        $next = self::couponFirstPeriodDate($settlement, $maturity, $frequency, self::PERIOD_DATE_NEXT);

        if ($basis === FinancialConstants::BASIS_DAYS_PER_YEAR_NASD) {
            $settlementDate = Date::excelToDateTimeObject($settlement);
            $settlementEoM = Helpers::isLastDayOfMonth($settlementDate);
            if ($settlementEoM) {
                ++$settlement;
            }
        }

        return (float) DateTimeExcel\YearFrac::fraction($settlement, $next, $basis) * $daysPerYear;
    }

    
    public static function COUPNCD(
        mixed $settlement,
        mixed $maturity,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|float {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            self::validateCouponPeriod($settlement, $maturity);
            $frequency = FinancialValidations::validateFrequency($frequency);
            FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::couponFirstPeriodDate($settlement, $maturity, $frequency, self::PERIOD_DATE_NEXT);
    }

    
    public static function COUPNUM(
        mixed $settlement,
        mixed $maturity,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|int {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            self::validateCouponPeriod($settlement, $maturity);
            $frequency = FinancialValidations::validateFrequency($frequency);
            FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $yearsBetweenSettlementAndMaturity = DateTimeExcel\YearFrac::fraction(
            $settlement,
            $maturity,
            FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
        );

        return (int) ceil((float) $yearsBetweenSettlementAndMaturity * $frequency);
    }

    
    public static function COUPPCD(
        mixed $settlement,
        mixed $maturity,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|float {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            self::validateCouponPeriod($settlement, $maturity);
            $frequency = FinancialValidations::validateFrequency($frequency);
            FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::couponFirstPeriodDate($settlement, $maturity, $frequency, self::PERIOD_DATE_PREVIOUS);
    }

    private static function monthsDiff(DateTime $result, int $months, string $plusOrMinus, int $day, bool $lastDayFlag): void
    {
        $result->setDate((int) $result->format('Y'), (int) $result->format('m'), 1);
        $result->modify("$plusOrMinus $months months");
        $daysInMonth = (int) $result->format('t');
        $result->setDate((int) $result->format('Y'), (int) $result->format('m'), $lastDayFlag ? $daysInMonth : min($day, $daysInMonth));
    }

    private static function couponFirstPeriodDate(float $settlement, float $maturity, int $frequency, bool $next): float
    {
        $months = 12 / $frequency;

        $result = Date::excelToDateTimeObject($maturity);
        $day = (int) $result->format('d');
        $lastDayFlag = Helpers::isLastDayOfMonth($result);

        while ($settlement < Date::PHPToExcel($result)) {
            self::monthsDiff($result, $months, '-', $day, $lastDayFlag);
        }
        if ($next === true) {
            self::monthsDiff($result, $months, '+', $day, $lastDayFlag);
        }

        return (float) Date::PHPToExcel($result);
    }

    private static function validateCouponPeriod(float $settlement, float $maturity): void
    {
        if ($settlement >= $maturity) {
            throw new Exception(ExcelError::NAN());
        }
    }
}
