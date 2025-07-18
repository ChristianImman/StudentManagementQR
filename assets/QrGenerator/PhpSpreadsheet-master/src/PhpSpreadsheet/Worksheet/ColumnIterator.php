<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use Iterator as NativeIterator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;


class ColumnIterator implements NativeIterator
{
    
    private Worksheet $worksheet;

    
    private int $currentColumnIndex = 1;

    
    private int $startColumnIndex = 1;

    
    private int $endColumnIndex = 1;

    
    public function __construct(Worksheet $worksheet, string $startColumn = 'A', ?string $endColumn = null)
    {
        
        $this->worksheet = $worksheet;
        $this->resetEnd($endColumn);
        $this->resetStart($startColumn);
    }

    
    public function __destruct()
    {
        unset($this->worksheet);
    }

    
    public function resetStart(string $startColumn = 'A'): static
    {
        $startColumnIndex = Coordinate::columnIndexFromString($startColumn);
        if ($startColumnIndex > Coordinate::columnIndexFromString($this->worksheet->getHighestColumn())) {
            throw new Exception(
                "Start column ({$startColumn}) is beyond highest column ({$this->worksheet->getHighestColumn()})"
            );
        }

        $this->startColumnIndex = $startColumnIndex;
        if ($this->endColumnIndex < $this->startColumnIndex) {
            $this->endColumnIndex = $this->startColumnIndex;
        }
        $this->seek($startColumn);

        return $this;
    }

    
    public function resetEnd(?string $endColumn = null): static
    {
        $endColumn = $endColumn ?: $this->worksheet->getHighestColumn();
        $this->endColumnIndex = Coordinate::columnIndexFromString($endColumn);

        return $this;
    }

    
    public function seek(string $column = 'A'): static
    {
        $column = Coordinate::columnIndexFromString($column);
        if (($column < $this->startColumnIndex) || ($column > $this->endColumnIndex)) {
            throw new PhpSpreadsheetException(
                "Column $column is out of range ({$this->startColumnIndex} - {$this->endColumnIndex})"
            );
        }
        $this->currentColumnIndex = $column;

        return $this;
    }

    
    public function rewind(): void
    {
        $this->currentColumnIndex = $this->startColumnIndex;
    }

    
    public function current(): Column
    {
        return new Column($this->worksheet, Coordinate::stringFromColumnIndex($this->currentColumnIndex));
    }

    
    public function key(): string
    {
        return Coordinate::stringFromColumnIndex($this->currentColumnIndex);
    }

    
    public function next(): void
    {
        ++$this->currentColumnIndex;
    }

    
    public function prev(): void
    {
        --$this->currentColumnIndex;
    }

    
    public function valid(): bool
    {
        return $this->currentColumnIndex <= $this->endColumnIndex && $this->currentColumnIndex >= $this->startColumnIndex;
    }
}
