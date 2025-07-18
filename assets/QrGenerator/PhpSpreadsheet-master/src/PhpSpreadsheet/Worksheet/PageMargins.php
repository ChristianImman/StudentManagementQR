<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

class PageMargins
{
    
    private float $left = 0.7;

    
    private float $right = 0.7;

    
    private float $top = 0.75;

    
    private float $bottom = 0.75;

    
    private float $header = 0.3;

    
    private float $footer = 0.3;

    
    public function __construct()
    {
    }

    
    public function getLeft(): float
    {
        return $this->left;
    }

    
    public function setLeft(float $left): static
    {
        $this->left = $left;

        return $this;
    }

    
    public function getRight(): float
    {
        return $this->right;
    }

    
    public function setRight(float $right): static
    {
        $this->right = $right;

        return $this;
    }

    
    public function getTop(): float
    {
        return $this->top;
    }

    
    public function setTop(float $top): static
    {
        $this->top = $top;

        return $this;
    }

    
    public function getBottom(): float
    {
        return $this->bottom;
    }

    
    public function setBottom(float $bottom): static
    {
        $this->bottom = $bottom;

        return $this;
    }

    
    public function getHeader(): float
    {
        return $this->header;
    }

    
    public function setHeader(float $header): static
    {
        $this->header = $header;

        return $this;
    }

    
    public function getFooter(): float
    {
        return $this->footer;
    }

    
    public function setFooter(float $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public static function fromCentimeters(float $value): float
    {
        return $value / 2.54;
    }

    public static function toCentimeters(float $value): float
    {
        return $value * 2.54;
    }

    public static function fromMillimeters(float $value): float
    {
        return $value / 25.4;
    }

    public static function toMillimeters(float $value): float
    {
        return $value * 25.4;
    }

    public static function fromPoints(float $value): float
    {
        return $value / 72;
    }

    public static function toPoints(float $value): float
    {
        return $value * 72;
    }
}
