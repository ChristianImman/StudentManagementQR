<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\CashFlowValidations;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Interest
{
    private const FINANCIAL_MAX_ITERATIONS = 128;

    private const FINANCIAL_PRECISION = 1.0e-08;

    
    public static function payment(
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

        return $interestAndPrincipal->interest();
    }

    
    public static function schedulePayment(mixed $interestRate, mixed $period, mixed $numberOfPeriods, mixed $principleRemaining): string|float
    {
        $interestRate = Functions::flattenSingleValue($interestRate);
        $period = Functions::flattenSingleValue($period);
        $numberOfPeriods = Functions::flattenSingleValue($numberOfPeriods);
        $principleRemaining = Functions::flattenSingleValue($principleRemaining);

        try {
            $interestRate = CashFlowValidations::validateRate($interestRate);
            $period = CashFlowValidations::validateInt($period);
            $numberOfPeriods = CashFlowValidations::validateInt($numberOfPeriods);
            $principleRemaining = CashFlowValidations::validateFloat($principleRemaining);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($period <= 0 || $period > $numberOfPeriods) {
            return ExcelError::NAN();
        }

        
        $returnValue = 0;

        
        $principlePayment = ($principleRemaining * 1.0) / ($numberOfPeriods * 1.0);
        for ($i = 0; $i <= $period; ++$i) {
            $returnValue = $interestRate * $principleRemaining * -1;
            $principleRemaining -= $principlePayment;
            
            if ($i == $numberOfPeriods) {
                $returnValue = 0.0;
            }
        }

        return $returnValue;
    }

    
    public static function rate(
        mixed $numberOfPeriods,
        mixed $payment,
        mixed $presentValue,
        mixed $futureValue = 0.0,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD,
        mixed $guess = 0.1
    ): string|float {
        $numberOfPeriods = Functions::flattenSingleValue($numberOfPeriods);
        $payment = Functions::flattenSingleValue($payment);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $futureValue = ($futureValue === null) ? 0.0 : Functions::flattenSingleValue($futureValue);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);
        $guess = ($guess === null) ? 0.1 : Functions::flattenSingleValue($guess);

        try {
            $numberOfPeriods = CashFlowValidations::validateFloat($numberOfPeriods);
            $payment = CashFlowValidations::validateFloat($payment);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
            $type = CashFlowValidations::validatePeriodType($type);
            $guess = CashFlowValidations::validateFloat($guess);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $rate = $guess;
        
        $close = false;
        $iter = 0;
        while (!$close && $iter < self::FINANCIAL_MAX_ITERATIONS) {
            $nextdiff = self::rateNextGuess($rate, $numberOfPeriods, $payment, $presentValue, $futureValue, $type);
            if (!is_numeric($nextdiff)) {
                break;
            }
            $rate1 = $rate - $nextdiff;
            $close = abs($rate1 - $rate) < self::FINANCIAL_PRECISION;
            ++$iter;
            $rate = $rate1;
        }

        return $close ? $rate : ExcelError::NAN();
    }

    private static function rateNextGuess(float $rate, float $numberOfPeriods, float $payment, float $presentValue, float $futureValue, int $type): string|float
    {
        if ($rate == 0.0) {
            return ExcelError::NAN();
        }
        $tt1 = ($rate + 1) ** $numberOfPeriods;
        $tt2 = ($rate + 1) ** ($numberOfPeriods - 1);
        $numerator = $futureValue + $tt1 * $presentValue + $payment * ($tt1 - 1) * ($rate * $type + 1) / $rate;
        $denominator = $numberOfPeriods * $tt2 * $presentValue - $payment * ($tt1 - 1)
            * ($rate * $type + 1) / ($rate * $rate) + $numberOfPeriods
            * $payment * $tt2 * ($rate * $type + 1) / $rate + $payment * ($tt1 - 1) * $type / $rate;
        if ($denominator == 0) {
            return ExcelError::NAN();
        }

        return $numerator / $denominator;
    }
}
