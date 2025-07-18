<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Single
{
    
    public static function futureValue(mixed $principal, array $schedule): string|float
    {
        $principal = Functions::flattenSingleValue($principal);
        $schedule = Functions::flattenArray($schedule);

        try {
            $principal = CashFlowValidations::validateFloat($principal);

            foreach ($schedule as $rate) {
                $rate = CashFlowValidations::validateFloat($rate);
                $principal *= 1 + $rate;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $principal;
    }

    
    public static function periods(mixed $rate, mixed $presentValue, mixed $futureValue): string|float
    {
        $rate = Functions::flattenSingleValue($rate);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $futureValue = Functions::flattenSingleValue($futureValue);

        try {
            $rate = CashFlowValidations::validateRate($rate);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($rate <= 0.0 || $presentValue <= 0.0 || $futureValue <= 0.0) {
            return ExcelError::NAN();
        }

        return (log($futureValue) - log($presentValue)) / log(1 + $rate);
    }

    
    public static function interestRate(mixed $periods = 0.0, mixed $presentValue = 0.0, mixed $futureValue = 0.0): string|float
    {
        $periods = Functions::flattenSingleValue($periods);
        $presentValue = Functions::flattenSingleValue($presentValue);
        $futureValue = Functions::flattenSingleValue($futureValue);

        try {
            $periods = CashFlowValidations::validateFloat($periods);
            $presentValue = CashFlowValidations::validatePresentValue($presentValue);
            $futureValue = CashFlowValidations::validateFutureValue($futureValue);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        
        if ($periods <= 0.0 || $presentValue <= 0.0 || $futureValue < 0.0) {
            return ExcelError::NAN();
        }

        return ($futureValue / $presentValue) ** (1 / $periods) - 1;
    }
}
