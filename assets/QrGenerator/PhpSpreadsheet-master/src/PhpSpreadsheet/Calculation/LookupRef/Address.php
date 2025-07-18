<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\AddressHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Address
{
    use ArrayEnabled;

    public const ADDRESS_ABSOLUTE = 1;
    public const ADDRESS_COLUMN_RELATIVE = 2;
    public const ADDRESS_ROW_RELATIVE = 3;
    public const ADDRESS_RELATIVE = 4;

    public const REFERENCE_STYLE_A1 = true;
    public const REFERENCE_STYLE_R1C1 = false;

    
    public static function cell(mixed $row, mixed $column, mixed $relativity = 1, mixed $referenceStyle = true, mixed $sheetName = ''): array|string
    {
        if (
            is_array($row) || is_array($column)
            || is_array($relativity) || is_array($referenceStyle) || is_array($sheetName)
        ) {
            return self::evaluateArrayArguments(
                [self::class, __FUNCTION__],
                $row,
                $column,
                $relativity,
                $referenceStyle,
                $sheetName
            );
        }

        $relativity = ($relativity === null) ? 1 : (int) StringHelper::convertToString($relativity);
        $referenceStyle = $referenceStyle ?? true;
        $row = (int) StringHelper::convertToString($row);
        $column = (int) StringHelper::convertToString($column);

        if (($row < 1) || ($column < 1)) {
            return ExcelError::VALUE();
        }

        $sheetName = self::sheetName(StringHelper::convertToString($sheetName));

        if (is_int($referenceStyle)) {
            $referenceStyle = (bool) $referenceStyle;
        }
        if ((!is_bool($referenceStyle)) || $referenceStyle === self::REFERENCE_STYLE_A1) {
            return self::formatAsA1($row, $column, $relativity, $sheetName);
        }

        return self::formatAsR1C1($row, $column, $relativity, $sheetName);
    }

    private static function sheetName(string $sheetName): string
    {
        if ($sheetName > '') {
            if (str_contains($sheetName, ' ') || str_contains($sheetName, '[')) {
                $sheetName = "'{$sheetName}'";
            }
            $sheetName .= '!';
        }

        return $sheetName;
    }

    private static function formatAsA1(int $row, int $column, int $relativity, string $sheetName): string
    {
        $rowRelative = $columnRelative = '$';
        if (($relativity == self::ADDRESS_COLUMN_RELATIVE) || ($relativity == self::ADDRESS_RELATIVE)) {
            $columnRelative = '';
        }
        if (($relativity == self::ADDRESS_ROW_RELATIVE) || ($relativity == self::ADDRESS_RELATIVE)) {
            $rowRelative = '';
        }
        $column = Coordinate::stringFromColumnIndex($column);

        return "{$sheetName}{$columnRelative}{$column}{$rowRelative}{$row}";
    }

    private static function formatAsR1C1(int $row, int $column, int $relativity, string $sheetName): string
    {
        if (($relativity == self::ADDRESS_COLUMN_RELATIVE) || ($relativity == self::ADDRESS_RELATIVE)) {
            $column = "[{$column}]";
        }
        if (($relativity == self::ADDRESS_ROW_RELATIVE) || ($relativity == self::ADDRESS_RELATIVE)) {
            $row = "[{$row}]";
        }
        [$rowChar, $colChar] = AddressHelper::getRowAndColumnChars();

        return "{$sheetName}$rowChar{$row}$colChar{$column}";
    }
}
