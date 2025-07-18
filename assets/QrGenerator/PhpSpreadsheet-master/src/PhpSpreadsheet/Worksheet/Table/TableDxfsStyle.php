<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet\Table;

use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;

class TableDxfsStyle
{
    
    private ?int $headerRow = null;

    
    private ?int $firstRowStripe = null;

    
    private ?int $secondRowStripe = null;

    
    private ?Style $headerRowStyle = null;

    
    private ?Style $firstRowStripeStyle = null;

    
    private ?Style $secondRowStripeStyle = null;

    
    private string $name;

    
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    
    public function getName(): string
    {
        return $this->name;
    }

    
    public function setHeaderRow(int $row): self
    {
        $this->headerRow = $row;

        return $this;
    }

    
    public function getHeaderRow(): ?int
    {
        return $this->headerRow;
    }

    
    public function setFirstRowStripe(int $row): self
    {
        $this->firstRowStripe = $row;

        return $this;
    }

    
    public function getFirstRowStripe(): ?int
    {
        return $this->firstRowStripe;
    }

    
    public function setSecondRowStripe(int $row): self
    {
        $this->secondRowStripe = $row;

        return $this;
    }

    
    public function getSecondRowStripe(): ?int
    {
        return $this->secondRowStripe;
    }

    
    public function setHeaderRowStyle(Style $style): self
    {
        $this->headerRowStyle = $style;

        return $this;
    }

    
    public function getHeaderRowStyle(): ?Style
    {
        return $this->headerRowStyle;
    }

    
    public function setFirstRowStripeStyle(Style $style): self
    {
        $this->firstRowStripeStyle = $style;

        return $this;
    }

    
    public function getFirstRowStripeStyle(): ?Style
    {
        return $this->firstRowStripeStyle;
    }

    
    public function setSecondRowStripeStyle(Style $style): self
    {
        $this->secondRowStripeStyle = $style;

        return $this;
    }

    
    public function getSecondRowStripeStyle(): ?Style
    {
        return $this->secondRowStripeStyle;
    }
}
