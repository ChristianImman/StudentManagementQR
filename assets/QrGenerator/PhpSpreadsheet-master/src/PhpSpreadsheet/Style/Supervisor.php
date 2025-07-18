<?php

namespace PhpOffice\PhpSpreadsheet\Style;

use PhpOffice\PhpSpreadsheet\IComparable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class Supervisor implements IComparable
{
    
    protected bool $isSupervisor;

    
    protected $parent;

    
    protected ?string $parentPropertyName = null;

    
    public function __construct(bool $isSupervisor = false)
    {
        
        $this->isSupervisor = $isSupervisor;
    }

    
    public function bindParent(Spreadsheet|self $parent, ?string $parentPropertyName = null)
    {
        $this->parent = $parent;
        $this->parentPropertyName = $parentPropertyName;

        return $this;
    }

    
    public function getIsSupervisor(): bool
    {
        return $this->isSupervisor;
    }

    
    public function getActiveSheet(): Worksheet
    {
        return $this->parent->getActiveSheet();
    }

    
    public function getSelectedCells(): string
    {
        return $this->getActiveSheet()->getSelectedCells();
    }

    
    public function getActiveCell(): string
    {
        return $this->getActiveSheet()->getActiveCell();
    }

    
    public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ((is_object($value)) && ($key != 'parent')) {
                $this->$key = clone $value;
            } else {
                $this->$key = $value;
            }
        }
    }

    
    final public function exportArray(): array
    {
        return $this->exportArray1();
    }

    
    abstract protected function exportArray1(): array;

    
    final protected function exportArray2(array &$exportedArray, string $index, mixed $objOrValue): void
    {
        if ($objOrValue instanceof self) {
            $exportedArray[$index] = $objOrValue->exportArray();
        } else {
            $exportedArray[$index] = $objOrValue;
        }
    }

    
    abstract public function getSharedComponent(): mixed;

    
    abstract public function getStyleArray(array $array): array;
}
