<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Counts;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Minimum;

class Mean
{
    
    public static function geometric(mixed ...$args): float|int|string
    {
        $aArgs = Functions::flattenArray($args);

        $aMean = MathTrig\Operations::product($aArgs);
        if (is_numeric($aMean) && ($aMean > 0)) {
            $aCount = Counts::COUNT($aArgs);
            if (Minimum::min($aArgs) > 0) {
                return $aMean ** (1 / $aCount);
            }
        }

        return ExcelError::NAN();
    }

    
    public static function harmonic(mixed ...$args): string|float|int
    {
        
        $aArgs = Functions::flattenArray($args);
        if (Minimum::min($aArgs) < 0) {
            return ExcelError::NAN();
        }

        $returnValue = 0;
        $aCount = 0;
        foreach ($aArgs as $arg) {
            
            if ((is_numeric($arg)) && (!is_string($arg))) {
                if ($arg <= 0) {
                    return ExcelError::NAN();
                }
                $returnValue += (1 / $arg);
                ++$aCount;
            }
        }

        
        if ($aCount > 0) {
            return 1 / ($returnValue / $aCount);
        }

        return ExcelError::NA();
    }

    
    public static function trim(mixed ...$args): float|string
    {
        $aArgs = Functions::flattenArray($args);

        
        $percent = array_pop($aArgs);

        if ((is_numeric($percent)) && (!is_string($percent))) {
            if (($percent < 0) || ($percent > 1)) {
                return ExcelError::NAN();
            }

            $mArgs = [];
            foreach ($aArgs as $arg) {
                
                if ((is_numeric($arg)) && (!is_string($arg))) {
                    $mArgs[] = $arg;
                }
            }

            $discard = floor(Counts::COUNT($mArgs) * $percent / 2);
            sort($mArgs);

            for ($i = 0; $i < $discard; ++$i) {
                array_pop($mArgs);
                array_shift($mArgs);
            }

            return Averages::average($mArgs);
        }

        return ExcelError::VALUE();
    }
}
