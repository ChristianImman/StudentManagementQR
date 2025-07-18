<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Minimum;

class DMin extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria, bool $returnError = true): float|string|null
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return $returnError ? ExcelError::VALUE() : null;
        }

        return Minimum::min(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
