<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\CashFlowValidations;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Payments
{
    
    public static function annuity(
        mixed $interestRate,
        mixed $numberOfPeriods,
        mixed $presentValue,
        mixed $futureValue = 0,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ): string|float {
        $interestRate = Functions::flattenSingleValue($interestRate);
        $numberOfPeriods = Functions::flattenSingleValue($numberOfPeriods);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $futureValue = ($futureValue === null) ? 0.0 : Functions::flattenSingleValue($futureValue);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $interestRate = CashFlowValidations::validateRate($interestRate);
            $numberOfPeriods = CashFlowValidations::validateInt($numberOfPeriods);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($interestRate != 0.0) {
            return (-$futureValue - $presentValue * (1 + $interestRate) ** $numberOfPeriods)
                / (1 + $interestRate * $type) / (((1 + $interestRate) ** $numberOfPeriods - 1) / $interestRate);
        }

        return (-$presentValue - $futureValue) / $numberOfPeriods;
    }

    
    public static function interestPayment(
        mixed $interestRate,
        mixed $period,
        mixed $numberOfPeriods,
        mixed $presentValue,
        mixed $futureValue = 0,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ): string|float {
        $interestRate = Functions::flattenSingleValue($interestRate);
        $period = Functions::flattenSingleValue($period);
        $numberOfPeriods = Functions::flattenSingleValue($numberOfPeriods);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $futureValue = ($futureValue === null) ? 0.0 : Functions::flattenSingleValue($futureValue);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $interestRate = CashFlowValidations::validateRate($interestRate);
            $period = CashFlowValidations::validateInt($period);
            $numberOfPeriods = CashFlowValidations::validateInt($numberOfPeriods);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($period <= 0 || $period > $numberOfPeriods) {
            return ExcelError::NAN();
        }

        
        $interestAndPrincipal = new InterestAndPrincipal(
            $interestRate,
            $period,
            $numberOfPeriods,
            $presentValue,
            $futureValue,
            $type
        );

        return $interestAndPrincipal->principal();
    }
}
