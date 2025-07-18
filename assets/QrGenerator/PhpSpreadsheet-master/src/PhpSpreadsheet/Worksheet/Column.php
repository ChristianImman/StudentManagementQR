<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

class Column
{
    private Worksheet $worksheet;

    
    private string $columnIndex;

    
    public function __construct(Worksheet $worksheet, string $columnIndex = 'A')
    {
        
        $this->worksheet = $worksheet;
        $this->columnIndex = $columnIndex;
    }

    
    public function __destruct()
    {
        unset($this->worksheet);
    }

    
    public function getColumnIndex(): string
    {
        return $this->columnIndex;
    }

    
    public function getCellIterator(int $startRow = 1, ?int $endRow = null, bool $iterateOnlyExistingCells = false): ColumnCellIterator
    {
        return new ColumnCellIterator($this->worksheet, $this->columnIndex, $startRow, $endRow, $iterateOnlyExistingCells);
    }

    
    public function getRowIterator(int $startRow = 1, ?int $endRow = null, bool $iterateOnlyExistingCells = false): ColumnCellIterator
    {
        return $this->getCellIterator($startRow, $endRow, $iterateOnlyExistingCells);
    }

    
    public function isEmpty(int $definitionOfEmptyFlags = 0, int $startRow = 1, ?int $endRow = null): bool
    {
        $nullValueCellIsEmpty = (bool) ($definitionOfEmptyFlags & CellIterator::TREAT_NULL_VALUE_AS_EMPTY_CELL);
        $emptyStringCellIsEmpty = (bool) ($definitionOfEmptyFlags & CellIterator::TREAT_EMPTY_STRING_AS_EMPTY_CELL);

        $cellIterator = $this->getCellIterator($startRow, $endRow);
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $value = $cell->getValue();
            if ($value === null && $nullValueCellIsEmpty === true) {
                continue;
            }
            if ($value === '' && $emptyStringCellIsEmpty === true) {
                continue;
            }

            return false;
        }

        return true;
    }

    
    public function getWorksheet(): Worksheet
    {
        return $this->worksheet;
    }
}
