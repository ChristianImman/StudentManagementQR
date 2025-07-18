<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use Iterator as NativeIterator;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Collection\Cells;


abstract class CellIterator implements NativeIterator
{
    public const TREAT_NULL_VALUE_AS_EMPTY_CELL = 1;

    public const TREAT_EMPTY_STRING_AS_EMPTY_CELL = 2;

    public const IF_NOT_EXISTS_RETURN_NULL = false;

    public const IF_NOT_EXISTS_CREATE_NEW = true;

    
    protected Worksheet $worksheet;

    
    protected Cells $cellCollection;

    
    protected bool $onlyExistingCells = false;

    
    protected bool $ifNotExists = self::IF_NOT_EXISTS_CREATE_NEW;

    
    public function __destruct()
    {
        unset($this->worksheet, $this->cellCollection);
    }

    public function getIfNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function setIfNotExists(bool $ifNotExists = self::IF_NOT_EXISTS_CREATE_NEW): void
    {
        $this->ifNotExists = $ifNotExists;
    }

    
    public function getIterateOnlyExistingCells(): bool
    {
        return $this->onlyExistingCells;
    }

    
    abstract protected function adjustForExistingOnlyRange(): void;

    
    public function setIterateOnlyExistingCells(bool $value): void
    {
        $this->onlyExistingCells = (bool) $value;

        $this->adjustForExistingOnlyRange();
    }
}
