<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RowColumnInformation
{
    
    private static function cellAddressNullOrWhitespace($cellAddress): bool
    {
        return $cellAddress === null || (!is_array($cellAddress) && trim($cellAddress) === '');
    }

    private static function cellColumn(?Cell $cell): int
    {
        return ($cell !== null) ? Coordinate::columnIndexFromString($cell->getColumn()) : 1;
    }

    
    public static function COLUMN($cellAddress = null, ?Cell $cell = null): int|array
    {
        if (self::cellAddressNullOrWhitespace($cellAddress)) {
            return self::cellColumn($cell);
        }

        if (is_array($cellAddress)) {
            foreach ($cellAddress as $columnKey => $value) {
                $columnKey = (string) preg_replace('/[^a-z]/i', '', $columnKey);

                return Coordinate::columnIndexFromString($columnKey);
            }

            return self::cellColumn($cell);
        }

        $cellAddress = $cellAddress ?? '';
        if ($cell != null) {
            [,, $sheetName] = Helpers::extractWorksheet($cellAddress, $cell);
            [,, $cellAddress] = Helpers::extractCellAddresses($cellAddress, true, $cell->getWorksheet(), $sheetName);
        }
        [, $cellAddress] = Worksheet::extractSheetTitle($cellAddress, true);
        $cellAddress ??= '';

        if (str_contains($cellAddress, ':')) {
            [$startAddress, $endAddress] = explode(':', $cellAddress);
            $startAddress = (string) preg_replace('/[^a-z]/i', '', $startAddress);
            $endAddress = (string) preg_replace('/[^a-z]/i', '', $endAddress);

            return range(
                Coordinate::columnIndexFromString($startAddress),
                Coordinate::columnIndexFromString($endAddress)
            );
        }

        $cellAddress = (string) preg_replace('/[^a-z]/i', '', $cellAddress);

        return Coordinate::columnIndexFromString($cellAddress);
    }

    
    public static function COLUMNS($cellAddress = null)
    {
        if (self::cellAddressNullOrWhitespace($cellAddress)) {
            return 1;
        }
        if (!is_array($cellAddress)) {
            return ExcelError::VALUE();
        }

        reset($cellAddress);
        $isMatrix = (is_numeric(key($cellAddress)));
        [$columns, $rows] = Calculation::getMatrixDimensions($cellAddress);

        if ($isMatrix) {
            return $rows;
        }

        return $columns;
    }

    private static function cellRow(?Cell $cell): int
    {
        return ($cell !== null) ? $cell->getRow() : 1;
    }

    
    public static function ROW($cellAddress = null, ?Cell $cell = null): int|array
    {
        if (self::cellAddressNullOrWhitespace($cellAddress)) {
            return self::cellRow($cell);
        }

        if (is_array($cellAddress)) {
            foreach ($cellAddress as $rowKey => $rowValue) {
                foreach ($rowValue as $columnKey => $cellValue) {
                    return (int) preg_replace('/\D/', '', $rowKey);
                }
            }

            return self::cellRow($cell);
        }

        $cellAddress = $cellAddress ?? '';
        if ($cell !== null) {
            [,, $sheetName] = Helpers::extractWorksheet($cellAddress, $cell);
            [,, $cellAddress] = Helpers::extractCellAddresses($cellAddress, true, $cell->getWorksheet(), $sheetName);
        }
        [, $cellAddress] = Worksheet::extractSheetTitle($cellAddress, true);
        $cellAddress ??= '';
        if (str_contains($cellAddress, ':')) {
            [$startAddress, $endAddress] = explode(':', $cellAddress);
            $startAddress = (int) (string) preg_replace('/\D/', '', $startAddress);
            $endAddress = (int) (string) preg_replace('/\D/', '', $endAddress);

            return array_map(
                fn ($value): array => [$value],
                range($startAddress, $endAddress)
            );
        }
        [$cellAddress] = explode(':', $cellAddress);

        return (int) preg_replace('/\D/', '', $cellAddress);
    }

    
    public static function ROWS($cellAddress = null)
    {
        if (self::cellAddressNullOrWhitespace($cellAddress)) {
            return 1;
        }
        if (!is_array($cellAddress)) {
            return ExcelError::VALUE();
        }

        reset($cellAddress);
        $isMatrix = (is_numeric(key($cellAddress)));
        [$columns, $rows] = Calculation::getMatrixDimensions($cellAddress);

        if ($isMatrix) {
            return $columns;
        }

        return $rows;
    }
}
