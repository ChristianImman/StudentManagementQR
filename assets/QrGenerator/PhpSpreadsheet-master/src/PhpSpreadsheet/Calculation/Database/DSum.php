<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

class DSum extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria, bool $returnNull = false): null|float|string
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return $returnNull ? null : ExcelError::VALUE();
        }

        return MathTrig\Sum::sumIgnoringStrings(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
