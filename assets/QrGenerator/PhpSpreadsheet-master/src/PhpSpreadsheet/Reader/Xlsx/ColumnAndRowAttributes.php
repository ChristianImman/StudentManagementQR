<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\DefaultReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SimpleXMLElement;

class ColumnAndRowAttributes extends BaseParserClass
{
    private Worksheet $worksheet;

    private ?SimpleXMLElement $worksheetXml;

    public function __construct(Worksheet $workSheet, ?SimpleXMLElement $worksheetXml = null)
    {
        $this->worksheet = $workSheet;
        $this->worksheetXml = $worksheetXml;
    }

    
    private function setColumnAttributes(string $columnAddress, array $columnAttributes): void
    {
        if (isset($columnAttributes['xfIndex'])) {
            $this->worksheet->getColumnDimension($columnAddress)->setXfIndex($columnAttributes['xfIndex']);
        }
        if (isset($columnAttributes['visible'])) {
            $this->worksheet->getColumnDimension($columnAddress)->setVisible($columnAttributes['visible']);
        }
        if (isset($columnAttributes['collapsed'])) {
            $this->worksheet->getColumnDimension($columnAddress)->setCollapsed($columnAttributes['collapsed']);
        }
        if (isset($columnAttributes['outlineLevel'])) {
            $this->worksheet->getColumnDimension($columnAddress)->setOutlineLevel($columnAttributes['outlineLevel']);
        }
        if (isset($columnAttributes['width'])) {
            $this->worksheet->getColumnDimension($columnAddress)->setWidth($columnAttributes['width']);
        }
    }

    
    private function setRowAttributes(int $rowNumber, array $rowAttributes): void
    {
        if (isset($rowAttributes['xfIndex'])) {
            $this->worksheet->getRowDimension($rowNumber)->setXfIndex($rowAttributes['xfIndex']);
        }
        if (isset($rowAttributes['visible'])) {
            $this->worksheet->getRowDimension($rowNumber)->setVisible($rowAttributes['visible']);
        }
        if (isset($rowAttributes['collapsed'])) {
            $this->worksheet->getRowDimension($rowNumber)->setCollapsed($rowAttributes['collapsed']);
        }
        if (isset($rowAttributes['outlineLevel'])) {
            $this->worksheet->getRowDimension($rowNumber)->setOutlineLevel($rowAttributes['outlineLevel']);
        }
        if (isset($rowAttributes['rowHeight'])) {
            $this->worksheet->getRowDimension($rowNumber)->setRowHeight($rowAttributes['rowHeight']);
        }
    }

    public function load(?IReadFilter $readFilter = null, bool $readDataOnly = false, bool $ignoreRowsWithNoCells = false): void
    {
        if ($this->worksheetXml === null) {
            return;
        }
        if ($readFilter !== null && $readFilter::class === DefaultReadFilter::class) {
            $readFilter = null;
        }

        $columnsAttributes = [];
        $rowsAttributes = [];
        if (isset($this->worksheetXml->cols)) {
            $columnsAttributes = $this->readColumnAttributes($this->worksheetXml->cols, $readDataOnly);
        }

        if ($this->worksheetXml->sheetData && $this->worksheetXml->sheetData->row) {
            $rowsAttributes = $this->readRowAttributes($this->worksheetXml->sheetData->row, $readDataOnly, $ignoreRowsWithNoCells, $readFilter !== null);
        }

        
        $columnsAttributesAreSet = [];
        foreach ($columnsAttributes as $columnCoordinate => $columnAttributes) {
            if (
                $readFilter === null
                || !$this->isFilteredColumn($readFilter, $columnCoordinate, $rowsAttributes)
            ) {
                if (!isset($columnsAttributesAreSet[$columnCoordinate])) {
                    $this->setColumnAttributes($columnCoordinate, $columnAttributes);
                    $columnsAttributesAreSet[$columnCoordinate] = true;
                }
            }
        }

        $rowsAttributesAreSet = [];
        foreach ($rowsAttributes as $rowCoordinate => $rowAttributes) {
            if (
                $readFilter === null
                || !$this->isFilteredRow($readFilter, $rowCoordinate, $columnsAttributes)
            ) {
                if (!isset($rowsAttributesAreSet[$rowCoordinate])) {
                    $this->setRowAttributes($rowCoordinate, $rowAttributes);
                    $rowsAttributesAreSet[$rowCoordinate] = true;
                }
            }
        }
    }

    private function isFilteredColumn(IReadFilter $readFilter, string $columnCoordinate, array $rowsAttributes): bool
    {
        foreach ($rowsAttributes as $rowCoordinate => $rowAttributes) {
            if ($readFilter->readCell($columnCoordinate, $rowCoordinate, $this->worksheet->getTitle())) {
                return false;
            }
        }

        return true;
    }

    private function readColumnAttributes(SimpleXMLElement $worksheetCols, bool $readDataOnly): array
    {
        $columnAttributes = [];

        foreach ($worksheetCols->col as $columnx) {
            $column = $columnx->attributes();
            if ($column !== null) {
                $startColumn = Coordinate::stringFromColumnIndex((int) $column['min']);
                $endColumn = Coordinate::stringFromColumnIndex((int) $column['max']);
                ++$endColumn;
                for ($columnAddress = $startColumn; $columnAddress !== $endColumn; ++$columnAddress) {
                    $columnAttributes[$columnAddress] = $this->readColumnRangeAttributes($column, $readDataOnly);

                    if ((int) ($column['max']) == 16384) {
                        break;
                    }
                }
            }
        }

        return $columnAttributes;
    }

    private function readColumnRangeAttributes(?SimpleXMLElement $column, bool $readDataOnly): array
    {
        $columnAttributes = [];
        if ($column !== null) {
            if (isset($column['style']) && !$readDataOnly) {
                $columnAttributes['xfIndex'] = (int) $column['style'];
            }
            if (isset($column['hidden']) && self::boolean($column['hidden'])) {
                $columnAttributes['visible'] = false;
            }
            if (isset($column['collapsed']) && self::boolean($column['collapsed'])) {
                $columnAttributes['collapsed'] = true;
            }
            if (isset($column['outlineLevel']) && ((int) $column['outlineLevel']) > 0) {
                $columnAttributes['outlineLevel'] = (int) $column['outlineLevel'];
            }
            if (isset($column['width'])) {
                $columnAttributes['width'] = (float) $column['width'];
            }
        }

        return $columnAttributes;
    }

    private function isFilteredRow(IReadFilter $readFilter, int $rowCoordinate, array $columnsAttributes): bool
    {
        foreach ($columnsAttributes as $columnCoordinate => $columnAttributes) {
            if (!$readFilter->readCell($columnCoordinate, $rowCoordinate, $this->worksheet->getTitle())) {
                return true;
            }
        }

        return false;
    }

    private function readRowAttributes(SimpleXMLElement $worksheetRow, bool $readDataOnly, bool $ignoreRowsWithNoCells, bool $readFilterIsNotNull): array
    {
        $rowAttributes = [];

        foreach ($worksheetRow as $rowx) {
            $row = $rowx->attributes();
            if ($row !== null && (!$ignoreRowsWithNoCells || isset($rowx->c))) {
                $rowIndex = (int) $row['r'];
                if (isset($row['ht']) && !$readDataOnly) {
                    $rowAttributes[$rowIndex]['rowHeight'] = (float) $row['ht'];
                }
                if (isset($row['hidden']) && self::boolean($row['hidden'])) {
                    $rowAttributes[$rowIndex]['visible'] = false;
                }
                if (isset($row['collapsed']) && self::boolean($row['collapsed'])) {
                    $rowAttributes[$rowIndex]['collapsed'] = true;
                }
                if (isset($row['outlineLevel']) && (int) $row['outlineLevel'] > 0) {
                    $rowAttributes[$rowIndex]['outlineLevel'] = (int) $row['outlineLevel'];
                }
                if (isset($row['s']) && !$readDataOnly) {
                    $rowAttributes[$rowIndex]['xfIndex'] = (int) $row['s'];
                }
                if ($readFilterIsNotNull && empty($rowAttributes[$rowIndex])) {
                    $rowAttributes[$rowIndex]['exists'] = true;
                }
            }
        }

        return $rowAttributes;
    }
}
