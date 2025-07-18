<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Sum
{
    
    public static function sumIgnoringStrings(mixed ...$args): float|int|string
    {
        $returnValue = 0;

        
        foreach (Functions::flattenArray($args) as $arg) {
            
            if (is_numeric($arg)) {
                $returnValue += $arg;
            } elseif (ErrorValue::isError($arg)) {
                return $arg;
            }
        }

        return $returnValue;
    }

    
    public static function sumErroringStrings(mixed ...$args): float|int|string|array
    {
        $returnValue = 0;
        
        $aArgs = Functions::flattenArrayIndexed($args);
        foreach ($aArgs as $k => $arg) {
            
            if (is_numeric($arg)) {
                $returnValue += $arg;
            } elseif (is_bool($arg)) {
                $returnValue += (int) $arg;
            } elseif (ErrorValue::isError($arg)) {
                return $arg;
            } elseif ($arg !== null && !Functions::isCellValue($k)) {
                
                return ExcelError::VALUE();
            }
        }

        return $returnValue;
    }

    
    public static function product(mixed ...$args): string|int|float
    {
        $arrayList = $args;

        $wrkArray = Functions::flattenArray(array_shift($arrayList));
        $wrkCellCount = count($wrkArray);

        for ($i = 0; $i < $wrkCellCount; ++$i) {
            if ((!is_numeric($wrkArray[$i])) || (is_string($wrkArray[$i]))) {
                $wrkArray[$i] = 0;
            }
        }

        foreach ($arrayList as $matrixData) {
            $array2 = Functions::flattenArray($matrixData);
            $count = count($array2);
            if ($wrkCellCount != $count) {
                return ExcelError::VALUE();
            }

            foreach ($array2 as $i => $val) {
                if ((!is_numeric($val)) || (is_string($val))) {
                    $val = 0;
                }
                $wrkArray[$i] *= $val;
            }
        }

        return array_sum($wrkArray);
    }
}
