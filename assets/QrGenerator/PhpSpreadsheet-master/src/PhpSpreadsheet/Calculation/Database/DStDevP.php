<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Database;

use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\StandardDeviations;

class DStDevP extends DatabaseAbstract
{
    
    public static function evaluate(array $database, array|null|int|string $field, array $criteria): float|string
    {
        $field = self::fieldExtract($database, $field);
        if ($field === null) {
            return ExcelError::VALUE();
        }

        return StandardDeviations::STDEVP(
            self::getFilteredColumn($database, $field, $criteria)
        );
    }
}
