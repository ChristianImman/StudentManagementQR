<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Selection
{
    use ArrayEnabled;

    
    public static function choose(mixed $chosenEntry, mixed ...$chooseArgs): mixed
    {
        if (is_array($chosenEntry)) {
            return self::evaluateArrayArgumentsSubset([self::class, __FUNCTION__], 1, $chosenEntry, ...$chooseArgs);
        }

        $entryCount = count($chooseArgs) - 1;

        if (is_numeric($chosenEntry)) {
            --$chosenEntry;
        } else {
            return ExcelError::VALUE();
        }
        $chosenEntry = floor($chosenEntry);
        if (($chosenEntry < 0) || ($chosenEntry > $entryCount)) {
            return ExcelError::VALUE();
        }

        if (is_array($chooseArgs[$chosenEntry])) {
            return Functions::flattenArray($chooseArgs[$chosenEntry]);
        }

        return $chooseArgs[$chosenEntry];
    }
}
