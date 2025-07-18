<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Ods;

use PhpOffice\PhpSpreadsheet\Writer\Ods;

abstract class WriterPart
{
    
    private Ods $parentWriter;

    
    public function getParentWriter(): Ods
    {
        return $this->parentWriter;
    }

    
    public function __construct(Ods $writer)
    {
        $this->parentWriter = $writer;
    }

    abstract public function write(): string;
}
