<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use Iterator as NativeIterator;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;


class RowIterator implements NativeIterator
{
    
    private Worksheet $subject;

    
    private int $position = 1;

    
    private int $startRow = 1;

    
    private int $endRow = 1;

    
    public function __construct(Worksheet $subject, int $startRow = 1, ?int $endRow = null)
    {
        
        $this->subject = $subject;
        $this->resetEnd($endRow);
        $this->resetStart($startRow);
    }

    public function __destruct()
    {
        unset($this->subject);
    }

    
    public function resetStart(int $startRow = 1): static
    {
        if ($startRow > $this->subject->getHighestRow()) {
            throw new PhpSpreadsheetException(
                "Start row ({$startRow}) is beyond highest row ({$this->subject->getHighestRow()})"
            );
        }

        $this->startRow = $startRow;
        if ($this->endRow < $this->startRow) {
            $this->endRow = $this->startRow;
        }
        $this->seek($startRow);

        return $this;
    }

    
    public function resetEnd(?int $endRow = null): static
    {
        $this->endRow = $endRow ?: $this->subject->getHighestRow();

        return $this;
    }

    
    public function seek(int $row = 1): static
    {
        if (($row < $this->startRow) || ($row > $this->endRow)) {
            throw new PhpSpreadsheetException("Row $row is out of range ({$this->startRow} - {$this->endRow})");
        }
        $this->position = $row;

        return $this;
    }

    
    public function rewind(): void
    {
        $this->position = $this->startRow;
    }

    
    public function current(): Row
    {
        return new Row($this->subject, $this->position);
    }

    
    public function key(): int
    {
        return $this->position;
    }

    
    public function next(): void
    {
        ++$this->position;
    }

    
    public function prev(): void
    {
        --$this->position;
    }

    
    public function valid(): bool
    {
        return $this->position <= $this->endRow && $this->position >= $this->startRow;
    }
}
