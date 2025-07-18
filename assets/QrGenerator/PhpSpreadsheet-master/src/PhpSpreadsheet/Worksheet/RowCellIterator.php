<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;


class RowCellIterator extends CellIterator
{
    
    private int $currentColumnIndex;

    
    private int $rowIndex;

    
    private int $startColumnIndex = 1;

    
    private int $endColumnIndex = 1;

    
    public function __construct(Worksheet $worksheet, int $rowIndex = 1, string $startColumn = 'A', ?string $endColumn = null, bool $iterateOnlyExistingCells = false)
    {
        
        $this->worksheet = $worksheet;
        $this->cellCollection = $worksheet->getCellCollection();
        $this->rowIndex = $rowIndex;
        $this->resetEnd($endColumn);
        $this->resetStart($startColumn);
        $this->setIterateOnlyExistingCells($iterateOnlyExistingCells);
    }

    
    public function resetStart(string $startColumn = 'A'): static
    {
        $this->startColumnIndex = Coordinate::columnIndexFromString($startColumn);
        $this->adjustForExistingOnlyRange();
        $this->seek(Coordinate::stringFromColumnIndex($this->startColumnIndex));

        return $this;
    }

    
    public function resetEnd(?string $endColumn = null): static
    {
        $endColumn = $endColumn ?: $this->worksheet->getHighestColumn();
        $this->endColumnIndex = Coordinate::columnIndexFromString($endColumn);
        $this->adjustForExistingOnlyRange();

        return $this;
    }

    
    public function seek(string $column = 'A'): static
    {
        $columnId = Coordinate::columnIndexFromString($column);
        if ($this->onlyExistingCells && !($this->cellCollection->has($column . $this->rowIndex))) {
            throw new PhpSpreadsheetException('In "IterateOnlyExistingCells" mode and Cell does not exist');
        }
        if (($columnId < $this->startColumnIndex) || ($columnId > $this->endColumnIndex)) {
            throw new PhpSpreadsheetException("Column $column is out of range ({$this->startColumnIndex} - {$this->endColumnIndex})");
        }
        $this->currentColumnIndex = $columnId;

        return $this;
    }

    
    public function rewind(): void
    {
        $this->currentColumnIndex = $this->startColumnIndex;
    }

    
    public function current(): ?Cell
    {
        $cellAddress = Coordinate::stringFromColumnIndex($this->currentColumnIndex) . $this->rowIndex;

        return $this->cellCollection->has($cellAddress)
            ? $this->cellCollection->get($cellAddress)
            : (
                $this->ifNotExists === self::IF_NOT_EXISTS_CREATE_NEW
                ? $this->worksheet->createNewCell($cellAddress)
                : null
            );
    }

    
    public function key(): string
    {
        return Coordinate::stringFromColumnIndex($this->currentColumnIndex);
    }

    
    public function next(): void
    {
        do {
            ++$this->currentColumnIndex;
        } while (($this->onlyExistingCells) && (!$this->cellCollection->has(Coordinate::stringFromColumnIndex($this->currentColumnIndex) . $this->rowIndex)) && ($this->currentColumnIndex <= $this->endColumnIndex));
    }

    
    public function prev(): void
    {
        do {
            --$this->currentColumnIndex;
        } while (($this->onlyExistingCells) && (!$this->cellCollection->has(Coordinate::stringFromColumnIndex($this->currentColumnIndex) . $this->rowIndex)) && ($this->currentColumnIndex >= $this->startColumnIndex));
    }

    
    public function valid(): bool
    {
        return $this->currentColumnIndex <= $this->endColumnIndex && $this->currentColumnIndex >= $this->startColumnIndex;
    }

    
    public function getCurrentColumnIndex(): int
    {
        return $this->currentColumnIndex;
    }

    
    protected function adjustForExistingOnlyRange(): void
    {
        if ($this->onlyExistingCells) {
            while ((!$this->cellCollection->has(Coordinate::stringFromColumnIndex($this->startColumnIndex) . $this->rowIndex)) && ($this->startColumnIndex <= $this->endColumnIndex)) {
                ++$this->startColumnIndex;
            }
            while ((!$this->cellCollection->has(Coordinate::stringFromColumnIndex($this->endColumnIndex) . $this->rowIndex)) && ($this->endColumnIndex >= $this->startColumnIndex)) {
                --$this->endColumnIndex;
            }
        }
    }
}
