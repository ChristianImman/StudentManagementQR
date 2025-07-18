<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Helper\Dimension as CssDimension;

class RowDimension extends Dimension
{
    
    private ?int $rowIndex;

    
    private float $height = -1;

    
    private bool $zeroHeight = false;

    
    public function __construct(?int $index = 0)
    {
        
        $this->rowIndex = $index;

        
        parent::__construct(null);
    }

    
    public function getRowIndex(): ?int
    {
        return $this->rowIndex;
    }

    
    public function setRowIndex(int $index): static
    {
        $this->rowIndex = $index;

        return $this;
    }

    
    public function getRowHeight(?string $unitOfMeasure = null): float
    {
        return ($unitOfMeasure === null || $this->height < 0)
            ? $this->height
            : (new CssDimension($this->height . CssDimension::UOM_POINTS))->toUnit($unitOfMeasure);
    }

    
    public function setRowHeight(float $height, ?string $unitOfMeasure = null): static
    {
        $this->height = ($unitOfMeasure === null || $height < 0)
            ? $height
            : (new CssDimension("{$height}{$unitOfMeasure}"))->height();

        return $this;
    }

    
    public function getZeroHeight(): bool
    {
        return $this->zeroHeight;
    }

    
    public function setZeroHeight(bool $zeroHeight): static
    {
        $this->zeroHeight = $zeroHeight;

        return $this;
    }
}
