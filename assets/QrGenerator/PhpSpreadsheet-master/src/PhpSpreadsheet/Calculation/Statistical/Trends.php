<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Trend\Trend;

class Trends
{
    use ArrayEnabled;

    private static function filterTrendValues(array &$array1, array &$array2): void
    {
        foreach ($array1 as $key => $value) {
            if ((is_bool($value)) || (is_string($value)) || ($value === null)) {
                unset($array1[$key], $array2[$key]);
            }
        }
    }

    
    private static function checkTrendArrays(mixed &$array1, mixed &$array2): void
    {
        if (!is_array($array1)) {
            $array1 = [$array1];
        }
        if (!is_array($array2)) {
            $array2 = [$array2];
        }

        $array1 = Functions::flattenArray($array1);
        $array2 = Functions::flattenArray($array2);

        self::filterTrendValues($array1, $array2);
        self::filterTrendValues($array2, $array1);

        
        $array1 = array_merge($array1);
        $array2 = array_merge($array2);
    }

    protected static function validateTrendArrays(array $yValues, array $xValues): void
    {
        $yValueCount = count($yValues);
        $xValueCount = count($xValues);

        if (($yValueCount === 0) || ($yValueCount !== $xValueCount)) {
            throw new Exception(ExcelError::NA());
        } elseif ($yValueCount === 1) {
            throw new Exception(ExcelError::DIV0());
        }
    }

    
    public static function CORREL(mixed $yValues, $xValues = null): float|string
    {
        if (($xValues === null) || (!is_array($yValues)) || (!is_array($xValues))) {
            return ExcelError::VALUE();
        }

        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getCorrelation();
    }

    
    public static function COVAR(array $yValues, array $xValues): float|string
    {
        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getCovariance();
    }

    
    public static function FORECAST(mixed $xValue, array $yValues, array $xValues)
    {
        if (is_array($xValue)) {
            return self::evaluateArrayArgumentsSubset([self::class, __FUNCTION__], 1, $xValue, $yValues, $xValues);
        }

        try {
            $xValue = StatisticalValidations::validateFloat($xValue);
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getValueOfYForX($xValue);
    }

    
    public static function GROWTH(array $yValues, array $xValues = [], array $newValues = [], mixed $const = true): array
    {
        $yValues = Functions::flattenArray($yValues);
        $xValues = Functions::flattenArray($xValues);
        $newValues = Functions::flattenArray($newValues);
        $const = ($const === null) ? true : (bool) Functions::flattenSingleValue($const);

        $bestFitExponential = Trend::calculate(Trend::TREND_EXPONENTIAL, $yValues, $xValues, $const);
        if (empty($newValues)) {
            $newValues = $bestFitExponential->getXValues();
        }

        $returnArray = [];
        foreach ($newValues as $xValue) {
            $returnArray[0][] = [$bestFitExponential->getValueOfYForX($xValue)];
        }

        return $returnArray;
    }

    
    public static function INTERCEPT(array $yValues, array $xValues): float|string
    {
        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getIntersect();
    }

    
    public static function LINEST(array $yValues, ?array $xValues = null, mixed $const = true, mixed $stats = false): string|array
    {
        $const = ($const === null) ? true : (bool) Functions::flattenSingleValue($const);
        $stats = ($stats === null) ? false : (bool) Functions::flattenSingleValue($stats);
        if ($xValues === null) {
            $xValues = $yValues;
        }

        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues, $const);

        if ($stats === true) {
            return [
                [
                    $bestFitLinear->getSlope(),
                    $bestFitLinear->getIntersect(),
                ],
                [
                    $bestFitLinear->getSlopeSE(),
                    ($const === false) ? ExcelError::NA() : $bestFitLinear->getIntersectSE(),
                ],
                [
                    $bestFitLinear->getGoodnessOfFit(),
                    $bestFitLinear->getStdevOfResiduals(),
                ],
                [
                    $bestFitLinear->getF(),
                    $bestFitLinear->getDFResiduals(),
                ],
                [
                    $bestFitLinear->getSSRegression(),
                    $bestFitLinear->getSSResiduals(),
                ],
            ];
        }

        return [
            $bestFitLinear->getSlope(),
            $bestFitLinear->getIntersect(),
        ];
    }

    
    public static function LOGEST(array $yValues, ?array $xValues = null, mixed $const = true, mixed $stats = false): string|array
    {
        $const = ($const === null) ? true : (bool) Functions::flattenSingleValue($const);
        $stats = ($stats === null) ? false : (bool) Functions::flattenSingleValue($stats);
        if ($xValues === null) {
            $xValues = $yValues;
        }

        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        foreach ($yValues as $value) {
            if ($value < 0.0) {
                return ExcelError::NAN();
            }
        }

        $bestFitExponential = Trend::calculate(Trend::TREND_EXPONENTIAL, $yValues, $xValues, $const);

        if ($stats === true) {
            return [
                [
                    $bestFitExponential->getSlope(),
                    $bestFitExponential->getIntersect(),
                ],
                [
                    $bestFitExponential->getSlopeSE(),
                    ($const === false) ? ExcelError::NA() : $bestFitExponential->getIntersectSE(),
                ],
                [
                    $bestFitExponential->getGoodnessOfFit(),
                    $bestFitExponential->getStdevOfResiduals(),
                ],
                [
                    $bestFitExponential->getF(),
                    $bestFitExponential->getDFResiduals(),
                ],
                [
                    $bestFitExponential->getSSRegression(),
                    $bestFitExponential->getSSResiduals(),
                ],
            ];
        }

        return [
            $bestFitExponential->getSlope(),
            $bestFitExponential->getIntersect(),
        ];
    }

    
    public static function RSQ(array $yValues, array $xValues)
    {
        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getGoodnessOfFit();
    }

    
    public static function SLOPE(array $yValues, array $xValues)
    {
        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getSlope();
    }

    
    public static function STEYX(array $yValues, array $xValues): float|string
    {
        try {
            self::checkTrendArrays($yValues, $xValues);
            self::validateTrendArrays($yValues, $xValues);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues);

        return $bestFitLinear->getStdevOfResiduals();
    }

    
    public static function TREND(array $yValues, array $xValues = [], array $newValues = [], mixed $const = true): array
    {
        $yValues = Functions::flattenArray($yValues);
        $xValues = Functions::flattenArray($xValues);
        $newValues = Functions::flattenArray($newValues);
        $const = ($const === null) ? true : (bool) Functions::flattenSingleValue($const);

        $bestFitLinear = Trend::calculate(Trend::TREND_LINEAR, $yValues, $xValues, $const);
        if (empty($newValues)) {
            $newValues = $bestFitLinear->getXValues();
        }

        $returnArray = [];
        foreach ($newValues as $xValue) {
            $returnArray[0][] = [$bestFitLinear->getValueOfYForX($xValue)];
        }

        return $returnArray;
    }
}
