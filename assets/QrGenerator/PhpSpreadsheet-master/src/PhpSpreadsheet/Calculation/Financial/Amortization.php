<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class Amortization
{
    
    public static function AMORDEGRC(
        mixed $cost,
        mixed $purchased,
        mixed $firstPeriod,
        mixed $salvage,
        mixed $period,
        mixed $rate,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|float {
        $cost = Functions::flattenSingleValue($cost);
        $purchased = Functions::flattenSingleValue($purchased);
        $firstPeriod = Functions::flattenSingleValue($firstPeriod);
        $salvage = Functions::flattenSingleValue($salvage);
        $period = Functions::flattenSingleValue($period);
        $rate = Functions::flattenSingleValue($rate);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $cost = FinancialValidations::validateFloat($cost);
            $purchased = FinancialValidations::validateDate($purchased);
            $firstPeriod = FinancialValidations::validateDate($firstPeriod);
            $salvage = FinancialValidations::validateFloat($salvage);
            $period = FinancialValidations::validateInt($period);
            $rate = FinancialValidations::validateFloat($rate);
            $basis = FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $yearFracx = DateTimeExcel\YearFrac::fraction($purchased, $firstPeriod, $basis);
        if (is_string($yearFracx)) {
            return $yearFracx;
        }
        
        $yearFrac = $yearFracx;

        $amortiseCoeff = self::getAmortizationCoefficient($rate);

        $rate *= $amortiseCoeff;
        $rate = (float) (string) $rate; 
        $fNRate = round($yearFrac * $rate * $cost, 0);
        $cost -= $fNRate;
        $fRest = $cost - $salvage;

        for ($n = 0; $n < $period; ++$n) {
            $fNRate = round($rate * $cost, 0);
            $fRest -= $fNRate;

            if ($fRest < 0.0) {
                return match ($period - $n) {
                    1 => round($cost * 0.5, 0),
                    default => 0.0,
                };
            }
            $cost -= $fNRate;
        }

        return $fNRate;
    }

    
    public static function AMORLINC(
        mixed $cost,
        mixed $purchased,
        mixed $firstPeriod,
        mixed $salvage,
        mixed $period,
        mixed $rate,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|float {
        $cost = Functions::flattenSingleValue($cost);
        $purchased = Functions::flattenSingleValue($purchased);
        $firstPeriod = Functions::flattenSingleValue($firstPeriod);
        $salvage = Functions::flattenSingleValue($salvage);
        $period = Functions::flattenSingleValue($period);
        $rate = Functions::flattenSingleValue($rate);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $cost = FinancialValidations::validateFloat($cost);
            $purchased = FinancialValidations::validateDate($purchased);
            $firstPeriod = FinancialValidations::validateDate($firstPeriod);
            $salvage = FinancialValidations::validateFloat($salvage);
            $period = FinancialValidations::validateFloat($period);
            $rate = FinancialValidations::validateFloat($rate);
            $basis = FinancialValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $fOneRate = $cost * $rate;
        $fCostDelta = $cost - $salvage;
        
        $purchasedYear = DateTimeExcel\DateParts::year($purchased);
        $yearFracx = DateTimeExcel\YearFrac::fraction($purchased, $firstPeriod, $basis);
        if (is_string($yearFracx)) {
            return $yearFracx;
        }
        
        $yearFrac = $yearFracx;

        if (
            $basis == FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL
            && $yearFrac < 1
        ) {
            $temp = Functions::scalar($purchasedYear);
            if (is_int($temp) || is_string($temp)) {
                if (DateTimeExcel\Helpers::isLeapYear($temp)) {
                    $yearFrac *= 365 / 366;
                }
            }
        }

        $f0Rate = $yearFrac * $rate * $cost;
        $nNumOfFullPeriods = (int) (($cost - $salvage - $f0Rate) / $fOneRate);

        if ($period == 0) {
            return $f0Rate;
        } elseif ($period <= $nNumOfFullPeriods) {
            return $fOneRate;
        } elseif ($period == ($nNumOfFullPeriods + 1)) {
            return $fCostDelta - $fOneRate * $nNumOfFullPeriods - $f0Rate;
        }

        return 0.0;
    }

    private static function getAmortizationCoefficient(float $rate): float
    {
        
        
        
        
        
        
        $fUsePer = 1.0 / $rate;

        if ($fUsePer < 3.0) {
            return 1.0;
        } elseif ($fUsePer < 4.0) {
            return 1.5;
        } elseif ($fUsePer <= 6.0) {
            return 2.0;
        }

        return 2.5;
    }
}
