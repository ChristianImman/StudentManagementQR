<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

class DProduct extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria): string|float
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return ExcelError::VALUE();
        }

        return MathTrig\Operations::product(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
