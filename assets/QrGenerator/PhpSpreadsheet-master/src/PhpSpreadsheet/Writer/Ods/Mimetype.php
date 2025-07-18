<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Ods;

class Mimetype extends WriterPart
{
    
    public function write(): string
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }
}
