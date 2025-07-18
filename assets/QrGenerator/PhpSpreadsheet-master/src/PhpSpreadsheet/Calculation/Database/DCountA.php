<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Counts;

class DCountA extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria): string|int
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return ExcelError::VALUE();
        }

        return Counts::COUNTA(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
