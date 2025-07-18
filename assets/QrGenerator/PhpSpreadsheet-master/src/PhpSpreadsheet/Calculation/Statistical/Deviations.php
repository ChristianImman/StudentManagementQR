<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Deviations
{
    
    public static function sumSquares(mixed ...$args): string|float
    {
        $aArgs = Functions::flattenArrayIndexed($args);

        $aMean = Averages::average($aArgs);
        if (!is_numeric($aMean)) {
            return ExcelError::NAN();
        }

        
        $returnValue = 0.0;
        $aCount = -1;
        foreach ($aArgs as $k => $arg) {
            
            if (
                (is_bool($arg))
                && ((!Functions::isCellValue($k))
                    || (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_OPENOFFICE))
            ) {
                $arg = (int) $arg;
            }
            if ((is_numeric($arg)) && (!is_string($arg))) {
                $returnValue += ($arg - $aMean) ** 2;
                ++$aCount;
            }
        }

        return $aCount === 0 ? ExcelError::VALUE() : $returnValue;
    }

    
    public static function kurtosis(...$args): string|int|float
    {
        $aArgs = Functions::flattenArrayIndexed($args);
        $mean = Averages::average($aArgs);
        if (!is_numeric($mean)) {
            return ExcelError::DIV0();
        }
        $stdDev = (float) StandardDeviations::STDEV($aArgs);

        if ($stdDev > 0) {
            $count = $summer = 0;

            foreach ($aArgs as $k => $arg) {
                if ((is_bool($arg)) && (!Functions::isMatrixValue($k))) {
                } else {
                    
                    if ((is_numeric($arg)) && (!is_string($arg))) {
                        $summer += (($arg - $mean) / $stdDev) ** 4;
                        ++$count;
                    }
                }
            }

            if ($count > 3) {
                return $summer * ($count * ($count + 1)
                        / (($count - 1) * ($count - 2) * ($count - 3))) - (3 * ($count - 1) ** 2
                        / (($count - 2) * ($count - 3)));
            }
        }

        return ExcelError::DIV0();
    }

    
    public static function skew(...$args): string|int|float
    {
        $aArgs = Functions::flattenArrayIndexed($args);
        $mean = Averages::average($aArgs);
        if (!is_numeric($mean)) {
            return ExcelError::DIV0();
        }
        $stdDev = StandardDeviations::STDEV($aArgs);
        if ($stdDev === 0.0 || is_string($stdDev)) {
            return ExcelError::DIV0();
        }

        $count = $summer = 0;
        
        foreach ($aArgs as $k => $arg) {
            if ((is_bool($arg)) && (!Functions::isMatrixValue($k))) {
            } elseif (!is_numeric($arg)) {
                return ExcelError::VALUE();
            } else {
                
                if (!is_string($arg)) {
                    $summer += (($arg - $mean) / $stdDev) ** 3;
                    ++$count;
                }
            }
        }

        if ($count > 2) {
            return $summer * ($count / (($count - 1) * ($count - 2)));
        }

        return ExcelError::DIV0();
    }
}
