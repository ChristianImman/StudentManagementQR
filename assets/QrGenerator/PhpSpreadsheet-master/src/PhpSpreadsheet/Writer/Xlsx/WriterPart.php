<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

abstract class WriterPart
{
    
    private Xlsx $parentWriter;

    
    public function getParentWriter(): Xlsx
    {
        return $this->parentWriter;
    }

    
    public function __construct(Xlsx $writer)
    {
        $this->parentWriter = $writer;
    }
}
