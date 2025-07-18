<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;

class Minimum extends MaxMinBase
{
    
    public static function min(mixed ...$args): float|int|string
    {
        $returnValue = null;

        
        $aArgs = Functions::flattenArray($args);
        foreach ($aArgs as $arg) {
            if (ErrorValue::isError($arg)) {
                $returnValue = $arg;

                break;
            }
            
            if ((is_numeric($arg)) && (!is_string($arg))) {
                if (($returnValue === null) || ($arg < $returnValue)) {
                    $returnValue = $arg;
                }
            }
        }

        if ($returnValue === null) {
            return 0;
        }

        return $returnValue;
    }

    
    public static function minA(mixed ...$args): float|int|string
    {
        $returnValue = null;

        
        $aArgs = Functions::flattenArray($args);
        foreach ($aArgs as $arg) {
            if (ErrorValue::isError($arg)) {
                $returnValue = $arg;

                break;
            }
            
            if ((is_numeric($arg)) || (is_bool($arg)) || ((is_string($arg) && ($arg != '')))) {
                $arg = self::datatypeAdjustmentAllowStrings($arg);
                if (($returnValue === null) || ($arg < $returnValue)) {
                    $returnValue = $arg;
                }
            }
        }

        if ($returnValue === null) {
            return 0;
        }

        return $returnValue;
    }
}
