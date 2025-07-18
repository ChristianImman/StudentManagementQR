<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Rates
{
    
    public static function discount(
        mixed $settlement,
        mixed $maturity,
        mixed $price,
        mixed $redemption,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): float|string {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $price = Functions::flattenSingleValue($price);
        $redemption = Functions::flattenSingleValue($redemption);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $price = SecurityValidations::validatePrice($price);
            $redemption = SecurityValidations::validateRedemption($redemption);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($price <= 0.0) {
            return ExcelError::NAN();
        }

        $daysBetweenSettlementAndMaturity = Functions::scalar(DateTimeExcel\YearFrac::fraction($settlement, $maturity, $basis));
        if (!is_numeric($daysBetweenSettlementAndMaturity)) {
            
            return StringHelper::convertToString($daysBetweenSettlementAndMaturity);
        }

        return (1 - $price / $redemption) / $daysBetweenSettlementAndMaturity;
    }

    
    public static function interest(
        mixed $settlement,
        mixed $maturity,
        mixed $investment,
        mixed $redemption,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): float|string {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $investment = Functions::flattenSingleValue($investment);
        $redemption = Functions::flattenSingleValue($redemption);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $investment = SecurityValidations::validateFloat($investment);
            $redemption = SecurityValidations::validateRedemption($redemption);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($investment <= 0) {
            return ExcelError::NAN();
        }

        $daysBetweenSettlementAndMaturity = Functions::scalar(DateTimeExcel\YearFrac::fraction($settlement, $maturity, $basis));
        if (!is_numeric($daysBetweenSettlementAndMaturity)) {
            
            return StringHelper::convertToString($daysBetweenSettlementAndMaturity);
        }

        return (($redemption / $investment) - 1) / ($daysBetweenSettlementAndMaturity);
    }
}
