<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Coupons;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Helpers;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Price
{
    
    public static function price(
        mixed $settlement,
        mixed $maturity,
        mixed $rate,
        mixed $yield,
        mixed $redemption,
        mixed $frequency,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ): string|float {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $rate = Functions::flattenSingleValue($rate);
        $yield = Functions::flattenSingleValue($yield);
        $redemption = Functions::flattenSingleValue($redemption);
        $frequency = Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $rate = SecurityValidations::validateRate($rate);
            $yield = SecurityValidations::validateYield($yield);
            $redemption = SecurityValidations::validateRedemption($redemption);
            $frequency = SecurityValidations::validateFrequency($frequency);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $dsc = (float) Coupons::COUPDAYSNC($settlement, $maturity, $frequency, $basis);
        $e = (float) Coupons::COUPDAYS($settlement, $maturity, $frequency, $basis);
        $n = (int) Coupons::COUPNUM($settlement, $maturity, $frequency, $basis);
        $a = (float) Coupons::COUPDAYBS($settlement, $maturity, $frequency, $basis);

        $baseYF = 1.0 + ($yield / $frequency);
        $rfp = 100 * ($rate / $frequency);
        $de = $dsc / $e;

        $result = $redemption / $baseYF ** (--$n + $de);
        for ($k = 0; $k <= $n; ++$k) {
            $result += $rfp / ($baseYF ** ($k + $de));
        }
        $result -= $rfp * ($a / $e);

        return $result;
    }

    
    public static function priceDiscounted(
        mixed $settlement,
        mixed $maturity,
        mixed $discount,
        mixed $redemption,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ) {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $discount = Functions::flattenSingleValue($discount);
        $redemption = Functions::flattenSingleValue($redemption);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $discount = SecurityValidations::validateDiscount($discount);
            $redemption = SecurityValidations::validateRedemption($redemption);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $daysBetweenSettlementAndMaturity = Functions::scalar(DateTimeExcel\YearFrac::fraction($settlement, $maturity, $basis));
        if (!is_numeric($daysBetweenSettlementAndMaturity)) {
            
            return StringHelper::convertToString($daysBetweenSettlementAndMaturity);
        }

        return $redemption * (1 - $discount * $daysBetweenSettlementAndMaturity);
    }

    
    public static function priceAtMaturity(
        mixed $settlement,
        mixed $maturity,
        mixed $issue,
        mixed $rate,
        mixed $yield,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ) {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $issue = Functions::flattenSingleValue($issue);
        $rate = Functions::flattenSingleValue($rate);
        $yield = Functions::flattenSingleValue($yield);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $issue = SecurityValidations::validateIssueDate($issue);
            $rate = SecurityValidations::validateRate($rate);
            $yield = SecurityValidations::validateYield($yield);
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

        return (100 + (($daysBetweenIssueAndMaturity / $daysPerYear) * $rate * 100))
            / (1 + (($daysBetweenSettlementAndMaturity / $daysPerYear) * $yield))
            - (($daysBetweenIssueAndSettlement / $daysPerYear) * $rate * 100);
    }

    
    public static function received(
        mixed $settlement,
        mixed $maturity,
        mixed $investment,
        mixed $discount,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ) {
        $settlement = Functions::flattenSingleValue($settlement);
        $maturity = Functions::flattenSingleValue($maturity);
        $investment = Functions::flattenSingleValue($investment);
        $discount = Functions::flattenSingleValue($discount);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            $maturity = SecurityValidations::validateMaturityDate($maturity);
            SecurityValidations::validateSecurityPeriod($settlement, $maturity);
            $investment = SecurityValidations::validateFloat($investment);
            $discount = SecurityValidations::validateDiscount($discount);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($investment <= 0) {
            return ExcelError::NAN();
        }
        $daysBetweenSettlementAndMaturity = DateTimeExcel\YearFrac::fraction($settlement, $maturity, $basis);
        if (!is_numeric($daysBetweenSettlementAndMaturity)) {
            
            return StringHelper::convertToString(Functions::scalar($daysBetweenSettlementAndMaturity));
        }

        return $investment / (1 - ($discount * $daysBetweenSettlementAndMaturity));
    }
}
