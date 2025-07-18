<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Internal;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelArrayPseudoFunctions
{
    public static function single(string $cellReference, Cell $cell): mixed
    {
        $worksheet = $cell->getWorksheet();

        [$referenceWorksheetName, $referenceCellCoordinate] = Worksheet::extractSheetTitle($cellReference, true, true);
        if (preg_match('/^([$]?[a-z]{1,3})([$]?([0-9]{1,7})):([$]?[a-z]{1,3})([$]?([0-9]{1,7}))$/i', "$referenceCellCoordinate", $matches) === 1) {
            $ourRow = $cell->getRow();
            $firstRow = (int) $matches[3];
            $lastRow = (int) $matches[6];
            if ($ourRow < $firstRow || $ourRow > $lastRow || $matches[1] !== $matches[4]) {
                return ExcelError::VALUE();
            }
            $referenceCellCoordinate = $matches[1] . $ourRow;
        }
        $referenceCell = ($referenceWorksheetName === '')
            ? $worksheet->getCell((string) $referenceCellCoordinate)
            : $worksheet->getParentOrThrow()
                ->getSheetByNameOrThrow((string) $referenceWorksheetName)
                ->getCell((string) $referenceCellCoordinate);

        $result = $referenceCell->getCalculatedValue();
        while (is_array($result)) {
            $result = array_shift($result);
        }

        return $result;
    }

    public static function anchorArray(string $cellReference, Cell $cell): array|string
    {
        
        $worksheet = $cell->getWorksheet();

        [$referenceWorksheetName, $referenceCellCoordinate] = Worksheet::extractSheetTitle($cellReference, true, true);
        $referenceCell = ($referenceWorksheetName === '')
            ? $worksheet->getCell((string) $referenceCellCoordinate)
            : $worksheet->getParentOrThrow()
                ->getSheetByNameOrThrow((string) $referenceWorksheetName)
                ->getCell((string) $referenceCellCoordinate);

        
        
        

        $calcEngine = Calculation::getInstance($worksheet->getParent());
        $result = $calcEngine->calculateCellValue($referenceCell, false);
        if (!is_array($result)) {
            $result = ExcelError::REF();
        }

        
        
        

        
        
        









        






        
        
        






        return $result;
    }
}
