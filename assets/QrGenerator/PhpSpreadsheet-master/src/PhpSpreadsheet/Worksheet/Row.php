<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

class Row
{
    private Worksheet $worksheet;

    
    private int $rowIndex;

    
    public function __construct(Worksheet $worksheet, int $rowIndex = 1)
    {
        
        $this->worksheet = $worksheet;
        $this->rowIndex = $rowIndex;
    }

    
    public function __destruct()
    {
        unset($this->worksheet);
    }

    
    public function getRowIndex(): int
    {
        return $this->rowIndex;
    }

    
    public function getCellIterator(string $startColumn = 'A', ?string $endColumn = null, bool $iterateOnlyExistingCells = false): RowCellIterator
    {
        return new RowCellIterator($this->worksheet, $this->rowIndex, $startColumn, $endColumn, $iterateOnlyExistingCells);
    }

    
    public function getColumnIterator(string $startColumn = 'A', ?string $endColumn = null, bool $iterateOnlyExistingCells = false): RowCellIterator
    {
        return $this->getCellIterator($startColumn, $endColumn, $iterateOnlyExistingCells);
    }

    
    public function isEmpty(int $definitionOfEmptyFlags = 0, string $startColumn = 'A', ?string $endColumn = null): bool
    {
        $nullValueCellIsEmpty = (bool) ($definitionOfEmptyFlags & CellIterator::TREAT_NULL_VALUE_AS_EMPTY_CELL);
        $emptyStringCellIsEmpty = (bool) ($definitionOfEmptyFlags & CellIterator::TREAT_EMPTY_STRING_AS_EMPTY_CELL);

        $cellIterator = $this->getCellIterator($startColumn, $endColumn);
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
