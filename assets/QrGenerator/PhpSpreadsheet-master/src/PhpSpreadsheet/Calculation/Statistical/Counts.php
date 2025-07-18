<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcException;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;

class Counts extends AggregateBase
{
    
    public static function COUNT(mixed ...$args): int
    {
        $returnValue = 0;

        
        $aArgs = Functions::flattenArrayIndexed($args);
        foreach ($aArgs as $k => $arg) {
            $arg = self::testAcceptedBoolean($arg, $k);
            
            
            
            if (self::isAcceptedCountable($arg, $k, true)) {
                ++$returnValue;
            }
        }

        return $returnValue;
    }

    
    public static function COUNTA(mixed ...$args): int
    {
        $returnValue = 0;

        
        $aArgs = Functions::flattenArrayIndexed($args);
        foreach ($aArgs as $k => $arg) {
            
            if ($arg !== null || (!Functions::isCellValue($k))) {
                ++$returnValue;
            }
        }

        return $returnValue;
    }

    
    public static function COUNTBLANK(mixed $range): int
    {
        if ($range === null) {
            return 1;
        }
        if (!is_array($range) || array_key_exists(0, $range)) {
            throw new CalcException('Must specify range of cells, not any kind of literal');
        }
        $returnValue = 0;

        
        $aArgs = Functions::flattenArray($range);
        foreach ($aArgs as $arg) {
            
            if (($arg === null) || ((is_string($arg)) && ($arg == ''))) {
                ++$returnValue;
            }
        }

        return $returnValue;
    }
}
