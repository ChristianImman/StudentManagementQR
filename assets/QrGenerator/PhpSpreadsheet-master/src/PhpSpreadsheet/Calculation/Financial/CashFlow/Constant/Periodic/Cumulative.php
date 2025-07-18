<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\CashFlowValidations;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Constants as FinancialConstants;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Cumulative
{
    
    public static function interest(
        mixed $rate,
        mixed $periods,
        mixed $presentValue,
        mixed $start,
        mixed $end,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ): string|float|int {
        $rate = Functions::flattenSingleValue($rate);
        $periods = Functions::flattenSingleValue($periods);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $start = Functions::flattenSingleValue($start);
        $end = Functions::flattenSingleValue($end);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $rate = CashFlowValidations::validateRate($rate);
            $periods = CashFlowValidations::validateInt($periods);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $start = CashFlowValidations::validateInt($start);
            $end = CashFlowValidations::validateInt($end);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($start < 1 || $start > $end) {
            return ExcelError::NAN();
        }

        
        $interest = 0;
        for ($per = $start; $per <= $end; ++$per) {
            $ipmt = Interest::payment($rate, $per, $periods, $presentValue, 0, $type);
            if (is_string($ipmt)) {
                return $ipmt;
            }

            $interest += $ipmt;
        }

        return $interest;
    }

    
    public static function principal(
        mixed $rate,
        mixed $periods,
        mixed $presentValue,
        mixed $start,
        mixed $end,
        mixed $type = FinancialConstants::PAYMENT_END_OF_PERIOD
    ): string|float|int {
        $rate = Functions::flattenSingleValue($rate);
        $periods = Functions::flattenSingleValue($periods);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $start = Functions::flattenSingleValue($start);
        $end = Functions::flattenSingleValue($end);
        $type = ($type === null) ? FinancialConstants::PAYMENT_END_OF_PERIOD : Functions::flattenSingleValue($type);

        try {
            $rate = CashFlowValidations::validateRate($rate);
            $periods = CashFlowValidations::validateInt($periods);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $start = CashFlowValidations::validateInt($start);
            $end = CashFlowValidations::validateInt($end);
            $type = CashFlowValidations::validatePeriodType($type);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($start < 1 || $start > $end) {
            return ExcelError::VALUE();
        }

        
        $principal = 0;
        for ($per = $start; $per <= $end; ++$per) {
            $ppmt = Payments::interestPayment($rate, $per, $periods, $presentValue, 0, $type);
            if (is_string($ppmt)) {
                return $ppmt;
            }

            $principal += $ppmt;
        }

        return $principal;
    }
}
