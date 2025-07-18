<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Size
{
    
    public static function large(mixed ...$args)
    {
        $aArgs = Functions::flattenArray($args);
        $entry = array_pop($aArgs);

        if ((is_numeric($entry)) && (!is_string($entry))) {
            $entry = (int) floor($entry);

            $mArgs = self::filter($aArgs);
            $count = Counts::COUNT($mArgs);
            --$entry;
            if ($count === 0 || $entry < 0 || $entry >= $count) {
                return ExcelError::NAN();
            }
            rsort($mArgs);

            return $mArgs[$entry];
        }

        return ExcelError::VALUE();
    }

    
    public static function small(mixed ...$args)
    {
        $aArgs = Functions::flattenArray($args);

        $entry = array_pop($aArgs);

        if ((is_numeric($entry)) && (!is_string($entry))) {
            $entry = (int) floor($entry);

            $mArgs = self::filter($aArgs);
            $count = Counts::COUNT($mArgs);
            --$entry;
            if ($count === 0 || $entry < 0 || $entry >= $count) {
                return ExcelError::NAN();
            }
            sort($mArgs);

            return $mArgs[$entry];
        }

        return ExcelError::VALUE();
    }

    
    protected static function filter(array $args): array
    {
        $mArgs = [];

        foreach ($args as $arg) {
            
            if ((is_numeric($arg)) && (!is_string($arg))) {
                $mArgs[] = $arg;
            }
        }

        return $mArgs;
    }
}
