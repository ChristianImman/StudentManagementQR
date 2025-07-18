<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Averages extends AggregateBase
{
    
    public static function averageDeviations(mixed ...$args): string|float
    {
        $aArgs = Functions::flattenArrayIndexed($args);

        
        $returnValue = 0.0;

        $aMean = self::average(...$args);
        if ($aMean === ExcelError::DIV0()) {
            return ExcelError::NAN();
        } elseif ($aMean === ExcelError::VALUE()) {
            return ExcelError::VALUE();
        }

        $aCount = 0;
        foreach ($aArgs as $k => $arg) {
            $arg = self::testAcceptedBoolean($arg, $k);
            
            
            
            if ((is_string($arg)) && (!is_numeric($arg)) && (!Functions::isCellValue($k))) {
                return ExcelError::VALUE();
            }
            if (self::isAcceptedCountable($arg, $k)) {
                
                
                $returnValue += abs($arg - $aMean);
                ++$aCount;
            }
        }

        
        if ($aCount === 0) {
            return ExcelError::DIV0();
        }

        return $returnValue / $aCount;
    }

    
    public static function average(mixed ...$args): string|int|float
    {
        $returnValue = $aCount = 0;

        
        foreach (Functions::flattenArrayIndexed($args) as $k => $arg) {
            $arg = self::testAcceptedBoolean($arg, $k);
            
            
            
            if ((is_string($arg)) && (!is_numeric($arg)) && (!Functions::isCellValue($k))) {
                return ExcelError::VALUE();
            }
            if (self::isAcceptedCountable($arg, $k)) {
                
                $returnValue += $arg;
                ++$aCount;
            }
        }

        
        if ($aCount > 0) {
            return $returnValue / $aCount;
        }

        return ExcelError::DIV0();
    }

    
    public static function averageA(mixed ...$args): string|int|float
    {
        $returnValue = null;

        $aCount = 0;
        
        foreach (Functions::flattenArrayIndexed($args) as $k => $arg) {
            if (is_numeric($arg)) {
                
            } elseif (is_bool($arg)) {
                $arg = (int) $arg;
            } elseif (!Functions::isMatrixValue($k)) {
                $arg = 0;
            } else {
                return ExcelError::VALUE();
            }
            $returnValue += $arg;
            ++$aCount;
        }

        if ($aCount > 0) {
            return $returnValue / $aCount;
        }

        return ExcelError::DIV0();
    }

    
    public static function median(mixed ...$args): float|string
    {
        $aArgs = Functions::flattenArray($args);

        $returnValue = ExcelError::NAN();

        $aArgs = self::filterArguments($aArgs);
        $valueCount = count($aArgs);
        if ($valueCount > 0) {
            sort($aArgs, SORT_NUMERIC);
            $valueCount = $valueCount / 2;
            if ($valueCount == floor($valueCount)) {
                $returnValue = ($aArgs[$valueCount--] + $aArgs[$valueCount]) / 2;
            } else {
                $valueCount = (int) floor($valueCount);
                $returnValue = $aArgs[$valueCount];
            }
        }

        return $returnValue;
    }

    
    public static function mode(mixed ...$args): float|string
    {
        $returnValue = ExcelError::NA();

        
        $aArgs = Functions::flattenArray($args);
        $aArgs = self::filterArguments($aArgs);

        if (!empty($aArgs)) {
            return self::modeCalc($aArgs);
        }

        return $returnValue;
    }

    protected static function filterArguments(array $args): array
    {
        return array_filter(
            $args,
            function ($value): bool {
                
                return is_numeric($value) && (!is_string($value));
            }
        );
    }

    
    private static function modeCalc(array $data): float|string
    {
        $frequencyArray = [];
        $index = 0;
        $maxfreq = 0;
        $maxfreqkey = '';
        $maxfreqdatum = '';
        foreach ($data as $datum) {
            $found = false;
            ++$index;
            foreach ($frequencyArray as $key => $value) {
                if ((string) $value['value'] == (string) $datum) {
                    ++$frequencyArray[$key]['frequency'];
                    $freq = $frequencyArray[$key]['frequency'];
                    if ($freq > $maxfreq) {
                        $maxfreq = $freq;
                        $maxfreqkey = $key;
                        $maxfreqdatum = $datum;
                    } elseif ($freq == $maxfreq) {
                        if ($frequencyArray[$key]['index'] < $frequencyArray[$maxfreqkey]['index']) {
                            $maxfreqkey = $key;
                            $maxfreqdatum = $datum;
                        }
                    }
                    $found = true;

                    break;
                }
            }

            if ($found === false) {
                $frequencyArray[] = [
                    'value' => $datum,
                    'frequency' => 1,
                    'index' => $index,
                ];
            }
        }

        if ($maxfreq <= 1) {
            return ExcelError::NA();
        }

        return $maxfreqdatum;
    }
}
