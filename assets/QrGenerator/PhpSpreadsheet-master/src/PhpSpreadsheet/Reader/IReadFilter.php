<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

interface IReadFilter
{
    
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool;
}
