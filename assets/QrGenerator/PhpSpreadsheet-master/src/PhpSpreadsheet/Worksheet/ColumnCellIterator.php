<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;


class ColumnCellIterator extends CellIterator
{
    
    private int $currentRow;

    
    private int $columnIndex;

    
    private int $startRow = 1;

    
    private int $endRow = 1;

    
    public function __construct(Worksheet $worksheet, string $columnIndex = 'A', int $startRow = 1, ?int $endRow = null, bool $iterateOnlyExistingCells = false)
    {
        
        $this->worksheet = $worksheet;
        $this->cellCollection = $worksheet->getCellCollection();
        $this->columnIndex = Coordinate::columnIndexFromString($columnIndex);
        $this->resetEnd($endRow);
        $this->resetStart($startRow);
        $this->setIterateOnlyExistingCells($iterateOnlyExistingCells);
    }

    
    public function resetStart(int $startRow = 1): static
    {
        $this->startRow = $startRow;
        $this->adjustForExistingOnlyRange();
        $this->seek($startRow);

        return $this;
    }

    
    public function resetEnd(?int $endRow = null): static
    {
        $this->endRow = $endRow ?: $this->worksheet->getHighestRow();
        $this->adjustForExistingOnlyRange();

        return $this;
    }

    
    public function seek(int $row = 1): static
    {
        if (
            $this->onlyExistingCells
            && (!$this->cellCollection->has(Coordinate::stringFromColumnIndex($this->columnIndex) . $row))
        ) {
            throw new PhpSpreadsheetException('In "IterateOnlyExistingCells" mode and Cell does not exist');
        }
        if (($row < $this->startRow) || ($row > $this->endRow)) {
            throw new PhpSpreadsheetException("Row $row is out of range ({$this->startRow} - {$this->endRow})");
        }
        $this->currentRow = $row;

        return $this;
    }

    
    public function rewind(): void
    {
        $this->currentRow = $this->startRow;
    }

    
    public function current(): ?Cell
    {
        $cellAddress = Coordinate::stringFromColumnIndex($this->columnIndex) . $this->currentRow;

        return $this->cellCollection->has($cellAddress)
            ? $this->cellCollection->get($cellAddress)
            : (
                $this->ifNotExists === self::IF_NOT_EXISTS_CREATE_NEW
                ? $this->worksheet->createNewCell($cellAddress)
                : null
            );
    }

    
    public function key(): int
    {
        return $this->currentRow;
    }

    
    public function next(): void
    {
        $columnAddress = Coordinate::stringFromColumnIndex($this->columnIndex);
        do {
            ++$this->currentRow;
        } while (
            ($this->onlyExistingCells)
            && ($this->currentRow <= $this->endRow)
            && (!$this->cellCollection->has($columnAddress . $this->currentRow))
        );
    }

    
    public function prev(): void
    {
        $columnAddress = Coordinate::stringFromColumnIndex($this->columnIndex);
        do {
            --$this->currentRow;
        } while (
            ($this->onlyExistingCells)
            && ($this->currentRow >= $this->startRow)
            && (!$this->cellCollection->has($columnAddress . $this->currentRow))
        );
    }

    
    public function valid(): bool
    {
        return $this->currentRow <= $this->endRow && $this->currentRow >= $this->startRow;
    }

    
    protected function adjustForExistingOnlyRange(): void
    {
        if ($this->onlyExistingCells) {
            $columnAddress = Coordinate::stringFromColumnIndex($this->columnIndex);
            while (
                (!$this->cellCollection->has($columnAddress . $this->startRow))
                && ($this->startRow <= $this->endRow)
            ) {
                ++$this->startRow;
            }
            while (
                (!$this->cellCollection->has($columnAddress . $this->endRow))
                && ($this->endRow >= $this->startRow)
            ) {
                --$this->endRow;
            }
        }
    }
}
