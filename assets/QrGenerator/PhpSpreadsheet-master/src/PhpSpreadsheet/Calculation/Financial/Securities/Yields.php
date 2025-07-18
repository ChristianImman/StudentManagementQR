<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Helpers;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Yields
{
    
    public static function yieldDiscounted(
        mixed $settlement,
        mixed $maturity,
        mixed $price,
        mixed $redemption,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ) {
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

        $daysPerYear = Helpers::daysPerYear(Functions::scalar(DateTimeExcel\DateParts::year($settlement)), $basis);
        if (!is_numeric($daysPerYear)) {
            return $daysPerYear;
        }
        $daysBetweenSettlementAndMaturity = Functions::scalar(DateTimeExcel\YearFrac::fraction($settlement, $maturity, $basis));
        if (!is_numeric($daysBetweenSettlementAndMaturity)) {
            
            return StringHelper::convertToString($daysBetweenSettlementAndMaturity);
        }
        $daysBetweenSettlementAndMaturity *= $daysPerYear;

        return (($redemption - $price) / $price) * ($daysPerYear / $daysBetweenSettlementAndMaturity);
    }

    
    public static function yieldAtMaturity(
        mixed $settlement,
        mixed $maturity,
        mixed $issue,
        mixed $rate,
        mixed $price,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ) {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $issue = Functions::flattenSingleValue($issue);
        $rate = Functions::flattenSingleValue($rate);
        $price = Functions::flattenSingleValue($price);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $issue = SecurityValidations::validateIssueDate($issue);
            $rate = SecurityValidations::validateRate($rate);
            $price = SecurityValidations::validatePrice($price);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $daysPerYear = Helpers::daysPerYear(Functions::scalar(DateTimeExcel\DateParts::year($settlement)), $basis);
        if (!is_numeric($daysPerYear)) {
            return $daysPerYear;
        }
        $daysBetweenIssueAndSettlement = Functions::scalar(DateTimeExcel\YearFrac::fraction($issue, $settlement, $basis));
        if (!is_numeric($daysBetweenIssueAndSettlement)) {
            
            return StringHelper::convertToString($daysBetweenIssueAndSettlement);
        }
        $daysBetweenIssueAndSettlement *= $daysPerYear;
        $daysBetweenIssueAndMaturity = Functions::scalar(DateTimeExcel\YearFrac::fraction($issue, $maturity, $basis));
        if (!is_numeric($daysBetweenIssueAndMaturity)) {
            
            return StringHelper::convertToString($daysBetweenIssueAndMaturity);
        }
        $daysBetweenIssueAndMaturity *= $daysPerYear;
        $daysBetweenSettlementAndMaturity = Functions::scalar(DateTimeExcel\YearFrac::fraction($settlement, $maturity, $basis));
        if (!is_numeric($daysBetweenSettlementAndMaturity)) {
            
            return StringHelper::convertToString($daysBetweenSettlementAndMaturity);
        }
        $daysBetweenSettlementAndMaturity *= $daysPerYear;

        return ((1 + (($daysBetweenIssueAndMaturity / $daysPerYear) * $rate)
                    - (($price / 100) + (($daysBetweenIssueAndSettlement / $daysPerYear) * $rate)))
                / (($price / 100) + (($daysBetweenIssueAndSettlement / $daysPerYear) * $rate)))
            * ($daysPerYear / $daysBetweenSettlementAndMaturity);
    }
}
