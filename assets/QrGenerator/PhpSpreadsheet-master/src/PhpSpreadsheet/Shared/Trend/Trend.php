<?php

namespace PhpOffice\PhpSpreadsheet\Shared\Trend;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class Trend
{
    const TREND_LINEAR = 'Linear';
    const TREND_LOGARITHMIC = 'Logarithmic';
    const TREND_EXPONENTIAL = 'Exponential';
    const TREND_POWER = 'Power';
    const TREND_POLYNOMIAL_2 = 'Polynomial_2';
    const TREND_POLYNOMIAL_3 = 'Polynomial_3';
    const TREND_POLYNOMIAL_4 = 'Polynomial_4';
    const TREND_POLYNOMIAL_5 = 'Polynomial_5';
    const TREND_POLYNOMIAL_6 = 'Polynomial_6';
    const TREND_BEST_FIT = 'Bestfit';
    const TREND_BEST_FIT_NO_POLY = 'Bestfit_no_Polynomials';

    
    private const TREND_TYPES = [
        self::TREND_LINEAR,
        self::TREND_LOGARITHMIC,
        self::TREND_EXPONENTIAL,
        self::TREND_POWER,
    ];

    
    private static array $trendTypePolynomialOrders = [
        self::TREND_POLYNOMIAL_2,
        self::TREND_POLYNOMIAL_3,
        self::TREND_POLYNOMIAL_4,
        self::TREND_POLYNOMIAL_5,
        self::TREND_POLYNOMIAL_6,
    ];

    
    private static array $trendCache = [];

    public static function calculate(string $trendType = self::TREND_BEST_FIT, array $yValues = [], array $xValues = [], bool $const = true): BestFit
    {
        
        $nY = count($yValues);
        $nX = count($xValues);

        
        if ($nX === 0) {
            $xValues = range(1, $nY);
        } elseif ($nY !== $nX) {
            
            throw new SpreadsheetException('Trend(): Number of elements in coordinate arrays do not match.');
        }

        $key = md5($trendType . $const . serialize($yValues) . serialize($xValues));
        
        switch ($trendType) {
            
            case self::TREND_LINEAR:
            case self::TREND_LOGARITHMIC:
            case self::TREND_EXPONENTIAL:
            case self::TREND_POWER:
                if (!isset(self::$trendCache[$key])) {
                    $className = '\PhpOffice\PhpSpreadsheet\Shared\Trend\\' . $trendType . 'BestFit';
                    self::$trendCache[$key] = new $className($yValues, $xValues, $const);
                }

                return self::$trendCache[$key];
            case self::TREND_POLYNOMIAL_2:
            case self::TREND_POLYNOMIAL_3:
            case self::TREND_POLYNOMIAL_4:
            case self::TREND_POLYNOMIAL_5:
            case self::TREND_POLYNOMIAL_6:
                if (!isset(self::$trendCache[$key])) {
                    $order = (int) substr($trendType, -1);
                    self::$trendCache[$key] = new PolynomialBestFit($order, $yValues, $xValues);
                }

                return self::$trendCache[$key];
            case self::TREND_BEST_FIT:
            case self::TREND_BEST_FIT_NO_POLY:
                
                
                $bestFit = [];
                $bestFitValue = [];
                foreach (self::TREND_TYPES as $trendMethod) {
                    $className = '\PhpOffice\PhpSpreadsheet\Shared\Trend\\' . $trendMethod . 'BestFit';
                    $bestFit[$trendMethod] = new $className($yValues, $xValues, $const);
                    $bestFitValue[$trendMethod] = $bestFit[$trendMethod]->getGoodnessOfFit();
                }
                if ($trendType !== self::TREND_BEST_FIT_NO_POLY) {
                    foreach (self::$trendTypePolynomialOrders as $trendMethod) {
                        $order = (int) substr($trendMethod, -1);
                        $bestFit[$trendMethod] = new PolynomialBestFit($order, $yValues, $xValues);
                        if ($bestFit[$trendMethod]->getError()) {
                            unset($bestFit[$trendMethod]);
                        } else {
                            $bestFitValue[$trendMethod] = $bestFit[$trendMethod]->getGoodnessOfFit();
                        }
                    }
                }
                
                arsort($bestFitValue);
                $bestFitType = key($bestFitValue);

                return $bestFit[$bestFitType];
            default:
                throw new SpreadsheetException("Unknown trend type $trendType");
        }
    }
}
