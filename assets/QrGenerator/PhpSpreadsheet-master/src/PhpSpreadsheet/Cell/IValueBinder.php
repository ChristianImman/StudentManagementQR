<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

interface IValueBinder
{
    
    public function bindValue(Cell $cell, mixed $value): bool;
}
