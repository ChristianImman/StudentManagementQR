<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\CashFlowValidations;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Periodic
{
    
    public static function futureValue(
        mixed $rate,
        mixed $numberOfPeriods,
        mixed $payment = 0.0,
        mixed $presentValue = 0.0,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ): string|float {
        $rate = Functions::flattenSingleValue($rate);
        $numberOfPeriods = Functions::flattenSingleValue($numberOfPeriods);
        $payment = ($payment === null) ? 0.0 : Functions::flattenSingleValue($payment);
        $presentValue = ($presentValue === null) ? 0.0 : Functions::flattenSingleValue($presentValue);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $rate = CashFlowValidations::validateRate($rate);
            $numberOfPeriods = CashFlowValidations::validateInt($numberOfPeriods);
            $payment = CashFlowValidations::validateFloat($payment);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return self::calculateFutureValue($rate, $numberOfPeriods, $payment, $presentValue, $type);
    }

    
    public static function presentValue(
        mixed $rate,
        mixed $numberOfPeriods,
        mixed $payment = 0.0,
        mixed $futureValue = 0.0,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ): string|float {
        $rate = Functions::flattenSingleValue($rate);
        $numberOfPeriods = Functions::flattenSingleValue($numberOfPeriods);
        $payment = ($payment === null) ? 0.0 : Functions::flattenSingleValue($payment);
        $futureValue = ($futureValue === null) ? 0.0 : Functions::flattenSingleValue($futureValue);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $rate = CashFlowValidations::validateRate($rate);
            $numberOfPeriods = CashFlowValidations::validateInt($numberOfPeriods);
            $payment = CashFlowValidations::validateFloat($payment);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($numberOfPeriods < 0) {
            return ExcelError::NAN();
        }

        return self::calculatePresentValue($rate, $numberOfPeriods, $payment, $futureValue, $type);
    }

    
    public static function periods(
        mixed $rate,
        mixed $payment,
        mixed $presentValue,
        mixed $futureValue = 0.0,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ) {
        $rate = Functions::flattenSingleValue($rate);
        $payment = Functions::flattenSingleValue($payment);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $futureValue = ($futureValue === null) ? 0.0 : Functions::flattenSingleValue($futureValue);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $rate = CashFlowValidations::validateRate($rate);
            $payment = CashFlowValidations::validateFloat($payment);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($payment == 0.0) {
            return ExcelError::NAN();
        }

        return self::calculatePeriods($rate, $payment, $presentValue, $futureValue, $type);
    }

    private static function calculateFutureValue(
        float $rate,
        int $numberOfPeriods,
        float $payment,
        float $presentValue,
        int $type
    ): float {
        if ($rate != 0) {
            return -$presentValue
                * (1 + $rate) ** $numberOfPeriods - $payment * (1 + $rate * $type) * ((1 + $rate) ** $numberOfPeriods - 1)
                    / $rate;
        }

        return -$presentValue - $payment * $numberOfPeriods;
    }

    private static function calculatePresentValue(
        float $rate,
        int $numberOfPeriods,
        float $payment,
        float $futureValue,
        int $type
    ): float {
        if ($rate != 0.0) {
            return (-$payment * (1 + $rate * $type)
                    * (((1 + $rate) ** $numberOfPeriods - 1) / $rate) - $futureValue) / (1 + $rate) ** $numberOfPeriods;
        }

        return -$futureValue - $payment * $numberOfPeriods;
    }

    private static function calculatePeriods(
        float $rate,
        float $payment,
        float $presentValue,
        float $futureValue,
        int $type
    ): string|float {
        if ($rate != 0.0) {
            if ($presentValue == 0.0) {
                return ExcelError::NAN();
            }

            return log(($payment * (1 + $rate * $type) / $rate - $futureValue)
                    / ($presentValue + $payment * (1 + $rate * $type) / $rate)) / log(1 + $rate);
        }

        return (-$presentValue - $futureValue) / $payment;
    }
}
