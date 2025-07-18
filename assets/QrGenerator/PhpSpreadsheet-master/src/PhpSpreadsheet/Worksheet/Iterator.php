<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;


class Iterator implements \Iterator
{
    
    private Spreadsheet $subject;

    
    private int $position = 0;

    
    public function __construct(Spreadsheet $subject)
    {
        
        $this->subject = $subject;
    }

    
    public function rewind(): void
    {
        $this->position = 0;
    }

    
    public function current(): Worksheet
    {
        return $this->subject->getSheet($this->position);
    }

    
    public function key(): int
    {
        return $this->position;
    }

    
    public function next(): void
    {
        ++$this->position;
    }

    
    public function valid(): bool
    {
        return $this->position < $this->subject->getSheetCount() && $this->position >= 0;
    }
}
