<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Indirect
{
    
    private static function a1Format(mixed $a1fmt): bool
    {
        $a1fmt = Functions::flattenSingleValue($a1fmt);
        if ($a1fmt === null) {
            return Helpers::CELLADDRESS_USE_A1;
        }
        if (is_string($a1fmt)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (bool) $a1fmt;
    }

    
    private static function validateAddress(array|string|null $cellAddress): string
    {
        $cellAddress = Functions::flattenSingleValue($cellAddress);
        if (!is_string($cellAddress) || !$cellAddress) {
            throw new Exception(ExcelError::REF());
        }

        return $cellAddress;
    }

    
    public static function INDIRECT($cellAddress, mixed $a1fmt, Cell $cell): string|array
    {
        [$baseCol, $baseRow] = Coordinate::indexesFromString($cell->getCoordinate());

        try {
            $a1 = self::a1Format($a1fmt);
            $cellAddress = self::validateAddress($cellAddress);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        [$cellAddress, $worksheet, $sheetName] = Helpers::extractWorksheet($cellAddress, $cell);

        if (preg_match('/^' . Calculation::CALCULATION_REGEXP_COLUMNRANGE_RELATIVE . '$/miu', $cellAddress, $matches)) {
            $cellAddress = self::handleRowColumnRanges($worksheet, ...explode(':', $cellAddress));
        } elseif (preg_match('/^' . Calculation::CALCULATION_REGEXP_ROWRANGE_RELATIVE . '$/miu', $cellAddress, $matches)) {
            $cellAddress = self::handleRowColumnRanges($worksheet, ...explode(':', $cellAddress));
        }

        try {
            [$cellAddress1, $cellAddress2, $cellAddress] = Helpers::extractCellAddresses($cellAddress, $a1, $cell->getWorkSheet(), $sheetName, $baseRow, $baseCol);
        } catch (Exception) {
            return ExcelError::REF();
        }

        if (
            (!preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/miu', $cellAddress1, $matches))
            || (($cellAddress2 !== null) && (!preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/miu', $cellAddress2, $matches)))
        ) {
            return ExcelError::REF();
        }

        return self::extractRequiredCells($worksheet, $cellAddress);
    }

    
    private static function extractRequiredCells(?Worksheet $worksheet, string $cellAddress): array
    {
        return Calculation::getInstance($worksheet !== null ? $worksheet->getParent() : null)
            ->extractCellRange($cellAddress, $worksheet, false);
    }

    private static function handleRowColumnRanges(?Worksheet $worksheet, string $start, string $end): string
    {
        
        if (ctype_digit($start) && $start <= 1048576) {
            
            $endColRef = ($worksheet !== null) ? $worksheet->getHighestDataColumn((int) $start) : AddressRange::MAX_COLUMN;

            return "A{$start}:{$endColRef}{$end}";
        } elseif (ctype_alpha($start) && strlen($start) <= 3) {
            
            $endRowRef = ($worksheet !== null) ? $worksheet->getHighestDataRow($start) : AddressRange::MAX_ROW;

            return "{$start}1:{$end}{$endRowRef}";
        }

        return "{$start}:{$end}";
    }
}
