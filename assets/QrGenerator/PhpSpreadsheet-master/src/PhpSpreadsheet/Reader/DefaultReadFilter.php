<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

class DefaultReadFilter implements IReadFilter
{
    
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return true;
    }
}
