<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Validations;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Offset
{
    
    public static function OFFSET(?string $cellAddress = null, $rows = 0, $columns = 0, $height = null, $width = null, ?Cell $cell = null): string|array
    {
        
        $rows = Functions::flattenSingleValue($rows);
        
        $columns = Functions::flattenSingleValue($columns);
        
        $height = Functions::flattenSingleValue($height);
        
        $width = Functions::flattenSingleValue($width);

        if ($cellAddress === null || $cellAddress === '') {
            return ExcelError::VALUE();
        }

        if (!is_object($cell)) {
            return ExcelError::REF();
        }
        $sheet = $cell->getParent()?->getParent(); 
        if ($sheet !== null) {
            $cellAddress = Validations::definedNameToCoordinate($cellAddress, $sheet);
        }

        [$cellAddress, $worksheet] = self::extractWorksheet($cellAddress, $cell);

        $startCell = $endCell = $cellAddress;
        if (strpos($cellAddress, ':')) {
            [$startCell, $endCell] = explode(':', $cellAddress);
        }
        [$startCellColumn, $startCellRow] = Coordinate::indexesFromString($startCell);
        [, $endCellRow, $endCellColumn] = Coordinate::indexesFromString($endCell);

        $startCellRow += $rows;
        $startCellColumn += $columns - 1;

        if (($startCellRow <= 0) || ($startCellColumn < 0)) {
            return ExcelError::REF();
        }

        $endCellColumn = self::adjustEndCellColumnForWidth($endCellColumn, $width, $startCellColumn, $columns);
        $startCellColumn = Coordinate::stringFromColumnIndex($startCellColumn + 1);

        $endCellRow = self::adustEndCellRowForHeight($height, $startCellRow, $rows, $endCellRow);

        if (($endCellRow <= 0) || ($endCellColumn < 0)) {
            return ExcelError::REF();
        }
        $endCellColumn = Coordinate::stringFromColumnIndex($endCellColumn + 1);

        $cellAddress = "{$startCellColumn}{$startCellRow}";
        if (($startCellColumn != $endCellColumn) || ($startCellRow != $endCellRow)) {
            $cellAddress .= ":{$endCellColumn}{$endCellRow}";
        }

        return self::extractRequiredCells($worksheet, $cellAddress);
    }

    private static function extractRequiredCells(?Worksheet $worksheet, string $cellAddress): array
    {
        return Calculation::getInstance($worksheet !== null ? $worksheet->getParent() : null)
            ->extractCellRange($cellAddress, $worksheet, false);
    }

    private static function extractWorksheet(?string $cellAddress, Cell $cell): array
    {
        $cellAddress = self::assessCellAddress($cellAddress ?? '', $cell);

        $sheetName = '';
        if (str_contains($cellAddress, '!')) {
            [$sheetName, $cellAddress] = Worksheet::extractSheetTitle($cellAddress, true, true);
        }

        $worksheet = ($sheetName !== '')
            ? $cell->getWorksheet()->getParentOrThrow()->getSheetByName($sheetName)
            : $cell->getWorksheet();

        return [$cellAddress, $worksheet];
    }

    private static function assessCellAddress(string $cellAddress, Cell $cell): string
    {
        if (preg_match('/^' . Calculation::CALCULATION_REGEXP_DEFINEDNAME . '$/mui', $cellAddress) !== false) {
            $cellAddress = Functions::expandDefinedName($cellAddress, $cell);
        }

        return $cellAddress;
    }

    
    private static function adjustEndCellColumnForWidth(string $endCellColumn, $width, int $startCellColumn, $columns): int
    {
        $endCellColumn = Coordinate::columnIndexFromString($endCellColumn) - 1;
        if (($width !== null) && (!is_object($width))) {
            $endCellColumn = $startCellColumn + (int) $width - 1;
        } else {
            $endCellColumn += (int) $columns;
        }

        return $endCellColumn;
    }

    
    private static function adustEndCellRowForHeight($height, int $startCellRow, $rows, int $endCellRow): int
    {
        if (($height !== null) && (!is_object($height))) {
            $endCellRow = $startCellRow + (int) $height - 1;
        } else {
            $endCellRow += (int) $rows;
        }

        return $endCellRow;
    }
}
