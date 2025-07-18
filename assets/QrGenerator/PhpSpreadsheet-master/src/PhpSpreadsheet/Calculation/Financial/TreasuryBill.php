<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class TreasuryBill
{
    
    public static function bondEquivalentYield(mixed $settlement, mixed $maturity, mixed $discount): string|float
    {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $discount = Functions::flattenSingleValue($discount);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            $discount = FinancialValidations::validateFloat($discount);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($discount <= 0) {
            return ExcelError::NAN();
        }

        $daysBetweenSettlementAndMaturity = $maturity - $settlement;
        $daysPerYear = Helpers::daysPerYear(
            Functions::scalar(DateTimeExcel\DateParts::year($maturity)),
            FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL
        );

        if ($daysBetweenSettlementAndMaturity > $daysPerYear || $daysBetweenSettlementAndMaturity < 0) {
            return ExcelError::NAN();
        }

        return (365 * $discount) / (360 - $discount * $daysBetweenSettlementAndMaturity);
    }

    
    public static function price(mixed $settlement, mixed $maturity, mixed $discount): string|float
    {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $discount = Functions::flattenSingleValue($discount);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            $discount = FinancialValidations::validateFloat($discount);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($discount <= 0) {
            return ExcelError::NAN();
        }

        $daysBetweenSettlementAndMaturity = $maturity - $settlement;
        $daysPerYear = Helpers::daysPerYear(
            Functions::scalar(DateTimeExcel\DateParts::year($maturity)),
            FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL
        );

        if ($daysBetweenSettlementAndMaturity > $daysPerYear || $daysBetweenSettlementAndMaturity < 0) {
            return ExcelError::NAN();
        }

        $price = 100 * (1 - (($discount * $daysBetweenSettlementAndMaturity) / 360));
        if ($price < 0.0) {
            return ExcelError::NAN();
        }

        return $price;
    }

    
    public static function yield(mixed $settlement, mixed $maturity, $price): string|float
    {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $price = Functions::flattenSingleValue($price);

        try {
            $settlement = FinancialValidations::validateSettlementDate($settlement);
            $maturity = FinancialValidations::validateMaturityDate($maturity);
            $price = FinancialValidations::validatePrice($price);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $daysBetweenSettlementAndMaturity = $maturity - $settlement;
        $daysPerYear = Helpers::daysPerYear(
            Functions::scalar(DateTimeExcel\DateParts::year($maturity)),
            FinancialConstants::BASIS_DAYS_PER_YEAR_ACTUAL
        );

        if ($daysBetweenSettlementAndMaturity > $daysPerYear || $daysBetweenSettlementAndMaturity < 0) {
            return ExcelError::NAN();
        }

        return ((100 - $price) / $price) * (360 / $daysBetweenSettlementAndMaturity);
    }
}
