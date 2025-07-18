<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities;

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\YearFrac;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class AccruedInterest
{
    public const ACCRINT_CALCMODE_ISSUE_TO_SETTLEMENT = true;

    public const ACCRINT_CALCMODE_FIRST_INTEREST_TO_SETTLEMENT = false;

    
    public static function periodic(
        mixed $issue,
        mixed $firstInterest,
        mixed $settlement,
        mixed $rate,
        mixed $parValue = 1000,
        mixed $frequency = FinancialConstants::FREQUENCY_ANNUAL,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD,
        mixed $calcMethod = self::ACCRINT_CALCMODE_ISSUE_TO_SETTLEMENT
    ) {
        $issue = Functions::flattenSingleValue($issue);
        $firstInterest = Functions::flattenSingleValue($firstInterest);
        $settlement = Functions::flattenSingleValue($settlement);
        $rate = Functions::flattenSingleValue($rate);
        $parValue = ($parValue === null) ? 1000 : Functions::flattenSingleValue($parValue);
        $frequency = ($frequency === null)
            ? FinancialConstants::FREQUENCY_ANNUAL
            : Functions::flattenSingleValue($frequency);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $issue = SecurityValidations::validateIssueDate($issue);
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            SecurityValidations::validateSecurityPeriod($issue, $settlement);
            $rate = SecurityValidations::validateRate($rate);
            $parValue = SecurityValidations::validateParValue($parValue);
            SecurityValidations::validateFrequency($frequency);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $daysBetweenIssueAndSettlement = Functions::scalar(YearFrac::fraction($issue, $settlement, $basis));
        if (!is_numeric($daysBetweenIssueAndSettlement)) {
            
            return StringHelper::convertToString($daysBetweenIssueAndSettlement);
        }
        $daysBetweenFirstInterestAndSettlement = Functions::scalar(YearFrac::fraction($firstInterest, $settlement, $basis));
        if (!is_numeric($daysBetweenFirstInterestAndSettlement)) {
            
            return StringHelper::convertToString($daysBetweenFirstInterestAndSettlement);
        }

        return $parValue * $rate * $daysBetweenIssueAndSettlement;
    }

    
    public static function atMaturity(
        mixed $issue,
        mixed $settlement,
        mixed $rate,
        mixed $parValue = 1000,
        mixed $basis = FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
    ) {
        $issue = Functions::flattenSingleValue($issue);
        $settlement = Functions::flattenSingleValue($settlement);
        $rate = Functions::flattenSingleValue($rate);
        $parValue = ($parValue === null) ? 1000 : Functions::flattenSingleValue($parValue);
        $basis = ($basis === null)
            ? FinancialConstants::BASIS_DAYS_PER_YEAR_NASD
            : Functions::flattenSingleValue($basis);

        try {
            $issue = SecurityValidations::validateIssueDate($issue);
            $settlement = SecurityValidations::validateSettlementDate($settlement);
            SecurityValidations::validateSecurityPeriod($issue, $settlement);
            $rate = SecurityValidations::validateRate($rate);
            $parValue = SecurityValidations::validateParValue($parValue);
            $basis = SecurityValidations::validateBasis($basis);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $daysBetweenIssueAndSettlement = Functions::scalar(YearFrac::fraction($issue, $settlement, $basis));
        if (!is_numeric($daysBetweenIssueAndSettlement)) {
            
            return StringHelper::convertToString($daysBetweenIssueAndSettlement);
        }

        return $parValue * $rate * $daysBetweenIssueAndSettlement;
    }
}
