<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Variances extends VarianceBase
{
    
    public static function VAR(mixed ...$args): float|string
    {
        $returnValue = ExcelError::DIV0();

        $summerA = $summerB = 0.0;

        
        $aArgs = Functions::flattenArray($args);
        $aCount = 0;
        foreach ($aArgs as $arg) {
            $arg = self::datatypeAdjustmentBooleans($arg);

            
            if ((is_numeric($arg)) && (!is_string($arg))) {
                $summerA += ($arg * $arg);
                $summerB += $arg;
                ++$aCount;
            }
        }

        if ($aCount > 1) {
            $summerA *= $aCount;
            $summerB *= $summerB;

            return ($summerA - $summerB) / ($aCount * ($aCount - 1));
        }

        return $returnValue;
    }

    
    public static function VARA(mixed ...$args): string|float
    {
        $returnValue = ExcelError::DIV0();

        $summerA = $summerB = 0.0;

        
        $aArgs = Functions::flattenArrayIndexed($args);
        $aCount = 0;
        foreach ($aArgs as $k => $arg) {
            if ((is_string($arg)) && (Functions::isValue($k))) {
                return ExcelError::VALUE();
            } elseif ((is_string($arg)) && (!Functions::isMatrixValue($k))) {
            } else {
                
                if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
                    $arg = self::datatypeAdjustmentAllowStrings($arg);
                    $summerA += ($arg * $arg);
                    $summerB += $arg;
                    ++$aCount;
                }
            }
        }

        if ($aCount > 1) {
            $summerA *= $aCount;
            $summerB *= $summerB;

            return ($summerA - $summerB) / ($aCount * ($aCount - 1));
        }

        return $returnValue;
    }

    
    public static function VARP(mixed ...$args): float|string
    {
        
        $returnValue = ExcelError::DIV0();

        $summerA = $summerB = 0.0;

        
        $aArgs = Functions::flattenArray($args);
        $aCount = 0;
        foreach ($aArgs as $arg) {
            $arg = self::datatypeAdjustmentBooleans($arg);

            
            if ((is_numeric($arg)) && (!is_string($arg))) {
                $summerA += ($arg * $arg);
                $summerB += $arg;
                ++$aCount;
            }
        }

        if ($aCount > 0) {
            $summerA *= $aCount;
            $summerB *= $summerB;

            return ($summerA - $summerB) / ($aCount * $aCount);
        }

        return $returnValue;
    }

    
    public static function VARPA(mixed ...$args): string|float
    {
        $returnValue = ExcelError::DIV0();

        $summerA = $summerB = 0.0;

        
        $aArgs = Functions::flattenArrayIndexed($args);
        $aCount = 0;
        foreach ($aArgs as $k => $arg) {
            if ((is_string($arg)) && (Functions::isValue($k))) {
                return ExcelError::VALUE();
            } elseif ((is_string($arg)) && (!Functions::isMatrixValue($k))) {
            } else {
                
                if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
                    $arg = self::datatypeAdjustmentAllowStrings($arg);
                    $summerA += ($arg * $arg);
                    $summerB += $arg;
                    ++$aCount;
                }
            }
        }

        if ($aCount > 0) {
            $summerA *= $aCount;
            $summerB *= $summerB;

            return ($summerA - $summerB) / ($aCount * $aCount);
        }

        return $returnValue;
    }
}
