<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Helper\Dimension as CssDimension;

class ColumnDimension extends Dimension
{
    
    private ?string $columnIndex;

    
    private float $width = -1;

    
    private bool $autoSize = false;

    
    public function __construct(?string $index = 'A')
    {
        
        $this->columnIndex = $index;

        
        parent::__construct(0);
    }

    
    public function getColumnIndex(): ?string
    {
        return $this->columnIndex;
    }

    
    public function setColumnIndex(string $index): self
    {
        $this->columnIndex = $index;

        return $this;
    }

    
    public function getColumnNumeric(): int
    {
        return Coordinate::columnIndexFromString($this->columnIndex ?? '');
    }

    
    public function setColumnNumeric(int $index): self
    {
        $this->columnIndex = Coordinate::stringFromColumnIndex($index);

        return $this;
    }

    
    public function getWidth(?string $unitOfMeasure = null): float
    {
        return ($unitOfMeasure === null || $this->width < 0)
            ? $this->width
            : (new CssDimension((string) $this->width))->toUnit($unitOfMeasure);
    }

    
    public function setWidth(float $width, ?string $unitOfMeasure = null): static
    {
        $this->width = ($unitOfMeasure === null || $width < 0)
            ? $width
            : (new CssDimension("{$width}{$unitOfMeasure}"))->width();

        return $this;
    }

    
    public function getAutoSize(): bool
    {
        return $this->autoSize;
    }

    
    public function setAutoSize(bool $autosizeEnabled): static
    {
        $this->autoSize = $autosizeEnabled;

        return $this;
    }
}
