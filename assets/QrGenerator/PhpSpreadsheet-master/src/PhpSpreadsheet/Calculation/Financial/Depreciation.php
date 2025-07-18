<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Financial;

use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Depreciation
{
    private static float $zeroPointZero = 0.0;

    
    public static function DB(mixed $cost, mixed $salvage, mixed $life, mixed $period, mixed $month = 12): string|float|int
    {
        $cost = Functions::flattenSingleValue($cost);
        $salvage = Functions::flattenSingleValue($salvage);
        $life = Functions::flattenSingleValue($life);
        $period = Functions::flattenSingleValue($period);
        $month = Functions::flattenSingleValue($month);

        try {
            $cost = self::validateCost($cost);
            $salvage = self::validateSalvage($salvage);
            $life = self::validateLife($life);
            $period = self::validatePeriod($period);
            $month = self::validateMonth($month);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($cost === self::$zeroPointZero) {
            return 0.0;
        }

        
        $fixedDepreciationRate = 1 - ($salvage / $cost) ** (1 / $life);
        $fixedDepreciationRate = round($fixedDepreciationRate, 3);

        
        
        $previousDepreciation = 0;
        $depreciation = 0;
        for ($per = 1; $per <= $period; ++$per) {
            if ($per == 1) {
                $depreciation = $cost * $fixedDepreciationRate * $month / 12;
            } elseif ($per == ($life + 1)) {
                $depreciation = ($cost - $previousDepreciation) * $fixedDepreciationRate * (12 - $month) / 12;
            } else {
                $depreciation = ($cost - $previousDepreciation) * $fixedDepreciationRate;
            }
            $previousDepreciation += $depreciation;
        }

        return $depreciation;
    }

    
    public static function DDB(mixed $cost, mixed $salvage, mixed $life, mixed $period, mixed $factor = 2.0): float|string
    {
        $cost = Functions::flattenSingleValue($cost);
        $salvage = Functions::flattenSingleValue($salvage);
        $life = Functions::flattenSingleValue($life);
        $period = Functions::flattenSingleValue($period);
        $factor = Functions::flattenSingleValue($factor);

        try {
            $cost = self::validateCost($cost);
            $salvage = self::validateSalvage($salvage);
            $life = self::validateLife($life);
            $period = self::validatePeriod($period);
            $factor = self::validateFactor($factor);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($period > $life) {
            return ExcelError::NAN();
        }

        
        
        $previousDepreciation = 0;
        $depreciation = 0;
        for ($per = 1; $per <= $period; ++$per) {
            $depreciation = min(
                ($cost - $previousDepreciation) * ($factor / $life),
                ($cost - $salvage - $previousDepreciation)
            );
            $previousDepreciation += $depreciation;
        }

        return $depreciation;
    }

    
    public static function SLN(mixed $cost, mixed $salvage, mixed $life): string|float
    {
        $cost = Functions::flattenSingleValue($cost);
        $salvage = Functions::flattenSingleValue($salvage);
        $life = Functions::flattenSingleValue($life);

        try {
            $cost = self::validateCost($cost, true);
            $salvage = self::validateSalvage($salvage, true);
            $life = self::validateLife($life, true);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($life === self::$zeroPointZero) {
            return ExcelError::DIV0();
        }

        return ($cost - $salvage) / $life;
    }

    
    public static function SYD(mixed $cost, mixed $salvage, mixed $life, mixed $period): string|float
    {
        $cost = Functions::flattenSingleValue($cost);
        $salvage = Functions::flattenSingleValue($salvage);
        $life = Functions::flattenSingleValue($life);
        $period = Functions::flattenSingleValue($period);

        try {
            $cost = self::validateCost($cost, true);
            $salvage = self::validateSalvage($salvage);
            $life = self::validateLife($life);
            $period = self::validatePeriod($period);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if ($period > $life) {
            return ExcelError::NAN();
        }

        $syd = (($cost - $salvage) * ($life - $period + 1) * 2) / ($life * ($life + 1));

        return $syd;
    }

    private static function validateCost(mixed $cost, bool $negativeValueAllowed = false): float
    {
        $cost = FinancialValidations::validateFloat($cost);
        if ($cost < 0.0 && $negativeValueAllowed === false) {
            throw new Exception(ExcelError::NAN());
        }

        return $cost;
    }

    private static function validateSalvage(mixed $salvage, bool $negativeValueAllowed = false): float
    {
        $salvage = FinancialValidations::validateFloat($salvage);
        if ($salvage < 0.0 && $negativeValueAllowed === false) {
            throw new Exception(ExcelError::NAN());
        }

        return $salvage;
    }

    private static function validateLife(mixed $life, bool $negativeValueAllowed = false): float
    {
        $life = FinancialValidations::validateFloat($life);
        if ($life < 0.0 && $negativeValueAllowed === false) {
            throw new Exception(ExcelError::NAN());
        }

        return $life;
    }

    private static function validatePeriod(mixed $period, bool $negativeValueAllowed = false): float
    {
        $period = FinancialValidations::validateFloat($period);
        if ($period <= 0.0 && $negativeValueAllowed === false) {
            throw new Exception(ExcelError::NAN());
        }

        return $period;
    }

    private static function validateMonth(mixed $month): int
    {
        $month = FinancialValidations::validateInt($month);
        if ($month < 1) {
            throw new Exception(ExcelError::NAN());
        }

        return $month;
    }

    private static function validateFactor(mixed $factor): float
    {
        $factor = FinancialValidations::validateFloat($factor);
        if ($factor <= 0.0) {
            throw new Exception(ExcelError::NAN());
        }

        return $factor;
    }
}
