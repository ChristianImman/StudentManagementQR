<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

abstract class Dimension
{
    
    private bool $visible = true;

    
    private int $outlineLevel = 0;

    
    private bool $collapsed = false;

    
    private ?int $xfIndex;

    
    public function __construct(?int $initialValue = null)
    {
        
        $this->xfIndex = $initialValue;
    }

    
    public function getVisible(): bool
    {
        return $this->visible;
    }

    
    public function setVisible(bool $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    
    public function getOutlineLevel(): int
    {
        return $this->outlineLevel;
    }

    
    public function setOutlineLevel(int $level)
    {
        if ($level < 0 || $level > 7) {
            throw new PhpSpreadsheetException('Outline level must range between 0 and 7.');
        }

        $this->outlineLevel = $level;

        return $this;
    }

    
    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }

    
    public function setCollapsed(bool $collapsed)
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    
    public function getXfIndex(): ?int
    {
        return $this->xfIndex;
    }

    
    public function setXfIndex(int $XfIndex)
    {
        $this->xfIndex = $XfIndex;

        return $this;
    }
}
