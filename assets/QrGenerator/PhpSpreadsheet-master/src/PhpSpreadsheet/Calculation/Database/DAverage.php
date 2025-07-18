<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;

class DAverage extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria): string|int|float
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return ExcelError::VALUE();
        }

        return Averages::average(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
