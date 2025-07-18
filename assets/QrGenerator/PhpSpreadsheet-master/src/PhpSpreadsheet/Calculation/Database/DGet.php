<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class DGet extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria): null|float|int|string
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return ExcelError::VALUE();
        }

        $columnData = self::getFilteredColumn($database, $field, $criteria);
        if (count($columnData) > 1) {
            return ExcelError::NAN();
        }

        $row = array_pop($columnData);

        return array_pop($row);
    }
}
