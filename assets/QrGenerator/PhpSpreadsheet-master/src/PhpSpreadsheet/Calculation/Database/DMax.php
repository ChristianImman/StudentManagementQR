<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Maximum;

class DMax extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria, bool $returnError = true): null|float|string
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return $returnError ? ExcelError::VALUE() : null;
        }

        return Maximum::max(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
