<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Counts;

class DCount extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria, bool $returnError = true): string|int
    {
        $field = self::fieldExtract($database, $field);
        if ($returnError && $field === null) {
            return ExcelError::VALUE();
        }

        return Counts::COUNT(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
